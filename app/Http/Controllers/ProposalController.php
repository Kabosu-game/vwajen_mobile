<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\Proposal;
use Illuminate\Http\Request;

class ProposalController extends Controller
{
    private function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:20000'],
            'timeline' => ['nullable', 'string', 'max:150'],
            'budget' => ['nullable', 'string', 'max:150'],
            'source_name' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'source_date' => ['nullable', 'date'],
        ];
    }

    /** Les propositions s'ajoutent à la version brouillon (ou à la version initiale non publiée). */
    public function store(Request $request, Program $program)
    {
        abort_unless($program->canManage($request->user()), 403);
        $version = $program->versions()->whereNull('published_at')->first();
        abort_unless($version, 422, __('Créez une nouvelle version pour modifier un programme publié.'));
        $data = $request->validate($this->rules());

        $proposal = $version->proposals()->create([
            'category_id' => $data['category_id'], 'title' => $data['title'], 'description' => $data['description'],
            'timeline' => $data['timeline'] ?? null, 'budget' => $data['budget'] ?? null,
            'position' => (int) $version->proposals()->max('position') + 1,
        ]);
        $this->attachSource($request, $proposal, $data);

        return back()->with('status', __('Proposition ajoutée.'));
    }

    public function update(Request $request, Proposal $proposal)
    {
        $program = $proposal->version->program;
        abort_unless($program->canManage($request->user()), 403);
        abort_if($proposal->version->published_at, 422, __('Cette version est publiée : créez une nouvelle version.'));
        $data = $request->validate($this->rules());
        $proposal->update(collect($data)->only(['category_id', 'title', 'description', 'timeline', 'budget'])->all());
        $this->attachSource($request, $proposal, $data);

        return back()->with('status', __('Proposition modifiée.'));
    }

    public function destroy(Request $request, Proposal $proposal)
    {
        abort_unless($proposal->version->program->canManage($request->user()), 403);
        abort_if($proposal->version->published_at, 422, __('Cette version est publiée : créez une nouvelle version.'));
        $proposal->delete();

        return back()->with('status', __('Proposition supprimée.'));
    }

    private function attachSource(Request $request, Proposal $proposal, array $data): void
    {
        if (! empty($data['source_name'])) {
            $proposal->sources()->create([
                'user_id' => $request->user()->id,
                'name' => $data['source_name'],
                'url' => $data['source_url'] ?? null,
                'published_on' => $data['source_date'] ?? null,
                'status' => 'provided',
                'provided_by_candidate' => true,
            ]);
        }
    }
}
