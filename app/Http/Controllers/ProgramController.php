<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Models\Program;
use App\Models\ProgramVersion;
use App\Services\ChangeLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Programmes : chaque programme possède plusieurs versions. La version courante est la dernière publiée ;
 * une version brouillon (published_at null) peut être préparée puis publiée.
 */
class ProgramController extends Controller
{
    private function authorizeOwner(Request $request, Program $program): void
    {
        abort_unless($program->canManage($request->user()), 403);
    }

    private function draftVersion(Program $program): ?ProgramVersion
    {
        return $program->versions()->whereNull('published_at')->first();
    }

    public function show(Request $request, Program $program)
    {
        abort_if($program->status === 'draft' && ! $program->canManage($request->user()), 404);

        return $this->renderVersion($request, $program, $program->currentVersion ?? $program->versions()->first());
    }

    public function version(Request $request, Program $program, ProgramVersion $version)
    {
        abort_unless($version->program_id === $program->id, 404);
        abort_if(! $version->published_at && ! $program->canManage($request->user()), 404);

        return $this->renderVersion($request, $program, $version);
    }

    private function renderVersion(Request $request, Program $program, ?ProgramVersion $version)
    {
        $program->load(['user.candidateProfile', 'documents', 'sources', 'versions', 'changeLogs.user']);
        $version?->load(['proposals.category', 'proposals.sources']);
        $categories = Category::allActive();
        $selectedCategory = $request->query('category');
        $grouped = $version ? $version->proposals
            ->when($selectedCategory, fn ($c) => $c->filter(fn ($p) => $p->category?->slug === $selectedCategory))
            ->groupBy('category_id') : collect();

        return view('programs.show', compact('program', 'version', 'categories', 'grouped', 'selectedCategory'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isCandidate() || $request->user()->account_type === 'official', 403, __('Réservé aux candidats.'));

        return view('programs.create');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isCandidate() || $user->account_type === 'official', 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:20000'],
        ]);

        $program = DB::transaction(function () use ($user, $data) {
            $program = Program::create(['user_id' => $user->id, 'title' => $data['title'], 'summary' => $data['summary'] ?? null]);
            $version = $program->versions()->create(['version_number' => 1, 'title' => $data['title'], 'summary' => $data['summary'] ?? null]);
            $program->update(['current_version_id' => $version->id]);

            return $program;
        });

        return redirect()->route('programs.edit', $program)->with('status', __('Programme créé. Ajoutez vos propositions par thème.'));
    }

    public function edit(Request $request, Program $program)
    {
        $this->authorizeOwner($request, $program);
        $version = $this->draftVersion($program) ?? $program->currentVersion;
        $version->load(['proposals.category', 'proposals.sources']);
        $program->load(['documents', 'sources', 'versions']);
        $categories = Category::allActive();

        return view('programs.edit', compact('program', 'version', 'categories'));
    }

    public function update(Request $request, Program $program)
    {
        $this->authorizeOwner($request, $program);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'summary' => ['nullable', 'string', 'max:20000'],
            'changelog' => ['nullable', 'string', 'max:2000'],
        ]);

        $version = $this->draftVersion($program);
        if (! $version) {
            abort_if($program->isPublished(), 422, __('Créez une nouvelle version pour modifier un programme publié.'));
            $version = $program->currentVersion;
        }
        $version->update($data);
        if (! $program->isPublished()) {
            $program->update(['title' => $data['title'], 'summary' => $data['summary'] ?? null]);
        }

        return back()->with('status', __('Programme enregistré.'));
    }

    /** Nouvelle version : copie la version courante (et ses propositions) en brouillon. */
    public function newVersion(Request $request, Program $program)
    {
        $this->authorizeOwner($request, $program);
        abort_if($this->draftVersion($program), 422, __('Une version brouillon existe déjà.'));

        DB::transaction(function () use ($program) {
            $current = $program->currentVersion()->with('proposals.sources')->first();
            $next = $program->versions()->create([
                'version_number' => (int) $program->versions()->max('version_number') + 1,
                'title' => $current->title,
                'summary' => $current->summary,
            ]);
            foreach ($current->proposals as $p) {
                $copy = $next->proposals()->create($p->only(['category_id', 'title', 'description', 'timeline', 'budget', 'position']));
                foreach ($p->sources as $s) {
                    $copy->sources()->create($s->only(['user_id', 'name', 'url', 'published_on', 'status', 'provided_by_candidate', 'verified_by', 'verified_at', 'note']));
                }
            }
        });

        return redirect()->route('programs.edit', $program)->with('status', __('Nouvelle version brouillon créée.'));
    }

    public function publish(Request $request, Program $program, Notifier $notifier)
    {
        $this->authorizeOwner($request, $program);
        $version = $this->draftVersion($program) ?? $program->currentVersion;
        abort_if($version->proposals()->count() === 0, 422, __('Ajoutez au moins une proposition avant de publier.'));

        $oldVersion = $program->isPublished() ? $program->currentVersion?->version_number : null;
        $version->update(['published_at' => now()]);
        $program->update([
            'status' => 'published', 'current_version_id' => $version->id, 'title' => $version->title,
            'summary' => $version->summary, 'published_at' => $program->published_at ?? now(), 'archived_at' => null,
        ]);
        ChangeLogger::record($program, 'version', $oldVersion, $version->version_number, $version->changelog ?: __('Publication de la version :v', ['v' => $version->version_number]));

        $notifier->broadcast($notifier->followersOf($program->user), 'system', $program->user,
            ':name a publié son programme (version :v)', ['name' => $program->user->name, 'v' => $version->version_number], $program->url(), $program);

        return redirect()->route('programs.show', $program)->with('status', __('Programme publié.'));
    }

    public function archive(Request $request, Program $program)
    {
        $this->authorizeOwner($request, $program);
        $program->update(['status' => 'archived', 'archived_at' => now()]);
        ChangeLogger::record($program, 'status', 'published', 'archived');

        return back()->with('status', __('Programme archivé. Il reste consultable publiquement.'));
    }

    public function unarchive(Request $request, Program $program)
    {
        $this->authorizeOwner($request, $program);
        $program->update(['status' => 'published', 'archived_at' => null]);
        ChangeLogger::record($program, 'status', 'archived', 'published');

        return back()->with('status', __('Programme réactivé.'));
    }

    public function destroy(Request $request, Program $program)
    {
        $this->authorizeOwner($request, $program);
        abort_if($program->published_at, 422, __('Un programme déjà publié ne peut pas être supprimé ; archivez-le.'));
        $program->delete();

        return redirect()->route('candidate.dashboard', 'program')->with('status', __('Brouillon supprimé.'));
    }

    public function addDocument(Request $request, Program $program)
    {
        $this->authorizeOwner($request, $program);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,odt,jpg,jpeg,png', 'max:20480'],
            'published_on' => ['nullable', 'date'],
        ]);
        $file = $request->file('file');
        $program->documents()->create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'path' => $file->store('programs/'.$program->id, 'public'),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'published_on' => $data['published_on'] ?? now(),
        ]);
        ChangeLogger::record($program, 'document', null, $data['title'], __('Document ajouté'));

        return back()->with('status', __('Document ajouté.'));
    }

    public function removeDocument(Request $request, Document $document)
    {
        $owner = $document->documentable;
        abort_unless($owner && method_exists($owner, 'canManage') && $owner->canManage($request->user()), 403);
        Storage::disk('public')->delete($document->path);
        ChangeLogger::record($owner, 'document', $document->title, null, __('Document retiré'));
        $document->delete();

        return back()->with('status', __('Document supprimé.'));
    }
}
