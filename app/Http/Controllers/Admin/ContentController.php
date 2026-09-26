<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hashtag;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ModerationService;
use App\Support\Morph;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Gestion des contenus : publications, commentaires, vidéos, Shorts, lives, débats, questions, événements, communautés, podcasts. */
class ContentController extends Controller
{
    public const TYPES = [
        'posts' => 'post', 'comments' => 'comment', 'videos' => 'video', 'shorts' => 'video', 'lives' => 'live', 'debates' => 'debate',
        'questions' => 'question', 'events' => 'event', 'communities' => 'community', 'podcasts' => 'podcast', 'discussions' => 'discussion', 'answers' => 'answer',
    ];

    public function index(Request $request, string $type)
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        $class = Morph::classFor(self::TYPES[$type]);
        $q = $class::query();
        $q->when($type === 'shorts', fn ($q) => $q->where('kind', 'short'))
            ->when($type === 'videos', fn ($q) => $q->where('kind', '!=', 'short'));

        $state = $request->query('state');
        if ($state === 'hidden') {
            $q->where('is_hidden', true);
        } elseif ($state === 'reported') {
            $q->whereHas('reports', fn ($r) => $r->where('status', 'pending'));
        }

        if ($s = $request->query('q')) {
            $cols = array_values(array_intersect(['body', 'title', 'name', 'description'], Schema::getColumnListing((new $class)->getTable())));
            $q->where(fn ($w) => collect($cols)->each(fn ($c) => $w->orWhere($c, 'like', "%$s%")));
        }
        if ($u = $request->query('user')) {
            $ownerCol = $type === 'communities' ? 'owner_id' : 'user_id';
            $q->whereIn($ownerCol, User::where('username', ltrim($u, '@'))->select('id'));
        }

        $items = $q->with($type === 'communities' ? 'owner' : 'user')->withCount(['reports' => fn ($r) => $r->where('status', 'pending')])
            ->latest()->paginate(30)->withQueryString();

        return view('admin.content.index', ['items' => $items, 'type' => $type, 'morph' => self::TYPES[$type]]);
    }

    public function action(Request $request, string $type, int $id, string $action, ModerationService $moderation)
    {
        $morph = self::TYPES[$type] ?? $type;
        $model = Morph::find($morph, $id);
        abort_unless($model, 404);
        // Un champ « Motif » laissé vide arrive à null (ConvertEmptyStringsToNull) : on retombe sur le motif par défaut.
        $reason = trim((string) $request->input('reason')) ?: __('Décision de modération');

        match ($action) {
            'hide' => $moderation->hide($model, $request->user(), $reason),
            'restore' => $moderation->restore($model, $request->user()),
            'delete' => $moderation->remove($model, $request->user(), $reason),
            default => abort(404),
        };

        return back()->with('status', __('Action effectuée.'));
    }

    public function hashtags(Request $request)
    {
        $hashtags = Hashtag::when($request->query('q'), fn ($q, $s) => $q->where('name', 'like', "%$s%"))
            ->orderByDesc($request->query('sort') === 'recent' ? 'last_used_at' : 'uses_count')->paginate(50)->withQueryString();

        return view('admin.content.hashtags', compact('hashtags'));
    }

    public function toggleHashtag(Hashtag $hashtag)
    {
        $hashtag->update(['is_blocked' => ! $hashtag->is_blocked]);
        AuditLogger::log($hashtag->is_blocked ? 'hashtag.block' : 'hashtag.unblock', null, ['hashtag' => $hashtag->name]);

        return back()->with('status', __('Hashtag mis à jour.'));
    }
}
