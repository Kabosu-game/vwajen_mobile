<?php

namespace App\Http\Controllers;

use App\Models\Ambassador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Programme ambassadeurs. */
class AmbassadorController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user() ? Ambassador::where('user_id', $request->user()->id)->first() : null;
        $ambassadors = Ambassador::where('status', 'approved')->with('user')->orderByDesc('referrals_count')->limit(30)->get();

        return view('ambassadors.index', compact('me', 'ambassadors'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_if(Ambassador::where('user_id', $user->id)->exists(), 422);
        $data = $request->validate([
            'city' => ['required', 'string', 'max:100'],
            'department' => ['nullable', 'in:'.implode(',', array_keys(config('vwajen.departments')))],
            'country' => ['required', 'string', 'size:2'],
            'motivation' => ['required', 'string', 'min:50', 'max:3000'],
        ]);
        Ambassador::create($data + ['user_id' => $user->id, 'referral_code' => strtoupper(Str::random(8))]);

        return back()->with('status', __('Candidature envoyée. Merci pour votre engagement !'));
    }
}
