<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\ElectionResult;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

/** Archives électorales et résultats publics. */
class ElectionController extends Controller
{
    public function index()
    {
        return view('admin.elections', ['elections' => Election::withCount('results')->with('results')->orderByDesc('held_on')->get()]);
    }

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:presidential,legislative,municipal,local,referendum'],
            'held_on' => ['required', 'date'],
            'round' => ['required', 'integer', 'min:1', 'max:3'],
            'description' => ['nullable', 'string', 'max:5000'],
            'source_name' => ['nullable', 'string', 'max:200'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'results_published' => ['nullable', 'boolean'],
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $e = Election::create($data + ['results_published' => $request->boolean('results_published')]);
        AuditLogger::log('election.create', $e);

        return back()->with('status', __('Élection ajoutée.'));
    }

    public function update(Request $request, Election $election)
    {
        $data = $request->validate($this->rules());
        $election->update($data + ['results_published' => $request->boolean('results_published')]);
        AuditLogger::log('election.update', $election);

        return back()->with('status', __('Élection mise à jour.'));
    }

    public function destroy(Election $election)
    {
        AuditLogger::log('election.delete', null, ['name' => $election->name]);
        $election->delete();

        return back()->with('status', __('Élection supprimée.'));
    }

    public function storeResult(Request $request, Election $election)
    {
        $data = $request->validate([
            'candidate_name' => ['required', 'string', 'max:200'],
            'username' => ['nullable', 'exists:users,username'],
            'party' => ['nullable', 'string', 'max:150'],
            'constituency' => ['nullable', 'string', 'max:150'],
            'department' => ['nullable', 'string', 'max:40'],
            'votes' => ['required', 'integer', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'elected' => ['nullable', 'boolean'],
        ]);
        $data['user_id'] = ! empty($data['username']) ? User::where('username', $data['username'])->value('id') : null;
        unset($data['username']);
        $election->results()->create($data + ['elected' => $request->boolean('elected')]);
        AuditLogger::log('election.result', $election, $data);

        return back()->with('status', __('Résultat ajouté.'));
    }

    public function destroyResult(ElectionResult $result)
    {
        $result->delete();

        return back()->with('status', __('Résultat supprimé.'));
    }
}
