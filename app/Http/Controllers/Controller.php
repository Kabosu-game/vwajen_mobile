<?php

namespace App\Http\Controllers;

use App\Support\Morph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

abstract class Controller
{
    /** Résout un contenu polymorphe interactif visible par l'utilisateur. */
    protected function interactable(string $type, int|string $id): Model
    {
        $model = Morph::findOrFail($type, $id, Morph::INTERACTABLE);
        $user = auth()->user();
        abort_if(($model->is_hidden ?? false) && ! $user?->isStaff() && $user?->id !== $model->user_id, 404);
        if (isset($model->user_id) && $user && $model->user && $user->isBlockedBetween($model->user)) {
            abort(403, __('Vous ne pouvez pas interagir avec ce contenu.'));
        }

        return $model;
    }

    /** Réponse JSON pour les requêtes AJAX, sinon retour arrière avec message. */
    protected function reply(Request $request, array $json, ?string $message = null)
    {
        if ($request->expectsJson()) {
            return response()->json($json + ['message' => $message]);
        }

        return back()->with('status', $message);
    }
}
