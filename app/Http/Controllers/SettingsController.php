<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Block;
use App\Models\Category;
use App\Models\Follow;
use App\Models\Hashtag;
use App\Models\Mute;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\AuditLogger;
use App\Services\DataExportService;
use App\Services\DeviceTracker;
use App\Services\FeedService;
use App\Services\MediaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.profile');
    }

    // ---------------- Profil ----------------

    public function profile(Request $request)
    {
        return view('settings.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request, MediaService $media)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'url', 'max:255'],
            'location' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'in:'.implode(',', array_keys(config('vwajen.departments')))],
            'country' => ['required', 'string', 'size:2'],
            'avatar' => ['nullable', 'image', 'max:8192'],
            'cover' => ['nullable', 'image', 'max:10240'],
            'remove_avatar' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);

        foreach (['avatar' => [400, 'avatars'], 'cover' => [1600, 'covers']] as $field => [$size, $dir]) {
            if ($request->hasFile($field)) {
                $media->delete($user->{$field});
                $data[$field] = $media->storeImage($request->file($field), $dir, $size);
            } elseif ($request->boolean('remove_'.$field)) {
                $media->delete($user->{$field});
                $data[$field] = null;
            } else {
                unset($data[$field]);
            }
        }
        unset($data['remove_avatar'], $data['remove_cover']);
        $data['country'] = strtoupper($data['country']);
        $data['is_diaspora'] = $data['country'] !== 'HT';
        $user->update($data);

        return back()->with('status', __('Profil mis à jour.'));
    }

    // ---------------- Compte ----------------

    public function account(Request $request)
    {
        return view('settings.account', ['user' => $request->user()]);
    }

    public function updateAccount(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9_]+$/', 'unique:users,username,'.$user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'current_password' => [$user->password ? 'required' : 'nullable', 'current_password'],
        ]);
        $emailChanged = strtolower($data['email']) !== $user->email;
        $user->forceFill(['username' => strtolower($data['username']), 'email' => strtolower($data['email'])]);
        if ($emailChanged) {
            $user->email_verified_at = null;
        }
        $user->save();
        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }
        AuditLogger::log('account.update', $user, ['email_changed' => $emailChanged]);

        return back()->with('status', $emailChanged ? __('Compte mis à jour. Vérifiez votre nouvelle adresse e-mail.') : __('Compte mis à jour.'));
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'current_password' => [$user->password ? 'required' : 'nullable', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);
        $user->forceFill(['password' => $request->password])->save();
        // Déconnecte les autres sessions après changement de mot de passe.
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();
        AuditLogger::log('account.password', $user);

        return back()->with('status', __('Mot de passe modifié. Les autres sessions ont été déconnectées.'));
    }

    // ---------------- Confidentialité ----------------

    public function privacy(Request $request)
    {
        return view('settings.privacy', ['user' => $request->user()]);
    }

    public function updatePrivacy(Request $request)
    {
        $data = $request->validate([
            'is_private' => ['nullable', 'boolean'],
            'allow_messages' => ['required', 'in:everyone,following,nobody'],
            'allow_comments' => ['required', 'in:everyone,following,nobody'],
            'allow_mentions' => ['required', 'in:everyone,following,nobody'],
            'show_location' => ['nullable', 'boolean'],
            'show_email' => ['nullable', 'boolean'],
            'show_phone' => ['nullable', 'boolean'],
            'show_political' => ['nullable', 'boolean'],
            'searchable' => ['nullable', 'boolean'],
        ]);
        foreach (['is_private', 'show_location', 'show_email', 'show_phone', 'show_political', 'searchable'] as $b) {
            $data[$b] = $request->boolean($b);
        }
        if ($request->user()->account_type !== 'personal') {
            $data['is_private'] = false; // les comptes publics (candidats, organisations, élus) restent publics
        }
        $request->user()->update($data);

        // Passage en public : les demandes en attente sont acceptées.
        if (! $data['is_private']) {
            $pending = Follow::where('following_id', $request->user()->id)->where('status', 'pending');
            $count = $pending->count();
            if ($count) {
                User::whereIn('id', (clone $pending)->select('follower_id'))->increment('following_count');
                $pending->update(['status' => 'accepted']);
                $request->user()->increment('followers_count', $count);
            }
        }

        return back()->with('status', __('Paramètres de confidentialité enregistrés.'));
    }

    // ---------------- Notifications ----------------

    public function notifications(Request $request)
    {
        return view('settings.notifications', ['user' => $request->user(), 'types' => User::NOTIFICATION_TYPES]);
    }

    public function updateNotifications(Request $request)
    {
        $prefs = [];
        foreach (User::NOTIFICATION_TYPES as $type) {
            foreach (['database', 'mail', 'push', 'sms'] as $channel) {
                $prefs[$type][$channel] = (bool) $request->input("prefs.$type.$channel", false);
            }
        }
        $request->user()->update(['notification_prefs' => $prefs]);

        return back()->with('status', __('Préférences de notification enregistrées.'));
    }

    // ---------------- Accessibilité & affichage ----------------

    public function accessibility(Request $request)
    {
        return view('settings.accessibility', ['user' => $request->user()]);
    }

    public function updateAccessibility(Request $request)
    {
        $data = $request->validate([
            'theme' => ['required', 'in:system,light,dark'],
            'font_size' => ['required', 'in:sm,md,lg,xl'],
            'high_contrast' => ['nullable', 'boolean'],
            'data_saver' => ['nullable', 'boolean'],
            'reduce_autoplay' => ['nullable', 'boolean'],
            'reduce_motion' => ['nullable', 'boolean'],
        ]);
        foreach (['high_contrast', 'data_saver', 'reduce_autoplay', 'reduce_motion'] as $b) {
            $data[$b] = $request->boolean($b);
        }
        $request->user()->update($data);

        return back()->with('status', __('Préférences d\'affichage enregistrées.'))
            ->withCookie(cookie()->forever('theme', $data['theme']))
            ->withCookie(cookie()->forever('data_saver', $data['data_saver'] ? '1' : '0'));
    }

    public function toggleTheme(Request $request)
    {
        $user = $request->user();
        $theme = $request->input('theme', $user->theme === 'dark' ? 'light' : 'dark');
        abort_unless(in_array($theme, ['system', 'light', 'dark'], true), 422);
        $user->update(['theme' => $theme]);

        return $this->reply($request, ['theme' => $theme])->withCookie(cookie()->forever('theme', $theme));
    }

    // ---------------- Langue ----------------

    public function language(Request $request)
    {
        return view('settings.language', ['user' => $request->user()]);
    }

    public function updateLanguage(Request $request)
    {
        $data = $request->validate([
            'locale' => ['required', 'in:'.implode(',', array_keys(config('vwajen.locales')))],
            'content_languages' => ['nullable', 'array'],
            'content_languages.*' => ['in:ht,fr,en'],
        ]);
        $user = $request->user();
        $prefs = $user->content_prefs ?? [];
        $prefs['languages'] = $data['content_languages'] ?? null;
        $user->update(['locale' => $data['locale'], 'content_prefs' => $prefs]);
        session(['locale' => $data['locale']]);

        return back()->with('status', __('Langue enregistrée.', [], $data['locale']))->withCookie(cookie()->forever('locale', $data['locale']));
    }

    // ---------------- Personnalisation ----------------

    public function interests(Request $request)
    {
        $user = $request->user();

        return view('settings.interests', [
            'user' => $user,
            'categories' => Category::allActive(),
            'hashtags' => $user->followedHashtags()->get(),
            'suggested' => Hashtag::where('is_blocked', false)->orderByDesc('uses_count')->limit(20)->get(),
            'following' => $user->following()->limit(50)->get(),
        ]);
    }

    public function updateInterests(Request $request)
    {
        $data = $request->validate([
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string', 'exists:categories,slug'],
            'hide_reposts' => ['nullable', 'boolean'],
            'feed_mode' => ['required', 'in:personalized,chronological,popular,recent'],
            'follow_hashtags' => ['nullable', 'string', 'max:500'],
        ]);
        $user = $request->user();
        $prefs = $user->content_prefs ?? [];
        $prefs['hide_reposts'] = $request->boolean('hide_reposts');
        $user->update(['interests' => $data['interests'] ?? [], 'content_prefs' => $prefs, 'feed_mode' => $data['feed_mode']]);

        if (! empty($data['follow_hashtags'])) {
            foreach (preg_split('/[\s,]+/', $data['follow_hashtags']) as $tag) {
                $tag = mb_strtolower(ltrim(trim($tag), '#'));
                if (preg_match('/^[\p{L}\p{N}_]{2,100}$/u', $tag)) {
                    $h = Hashtag::firstOrCreate(['name' => $tag]);
                    $user->followedHashtags()->syncWithoutDetaching([$h->id]);
                }
            }
        }
        FeedService::forget($user);

        return back()->with('status', __('Préférences de contenu enregistrées.'));
    }

    // ---------------- Sessions & appareils ----------------

    public function sessions(Request $request)
    {
        $sessions = DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get()
            ->map(function ($s) use ($request) {
                [$platform, $browser] = DeviceTracker::parse((string) $s->user_agent);
                $s->platform = $platform;
                $s->browser = $browser;
                $s->current = $s->id === $request->session()->getId();
                $s->last = Carbon::createFromTimestamp($s->last_activity);

                return $s;
            });

        return view('settings.sessions', compact('sessions'));
    }

    /** Déconnexion à distance d'une session. */
    public function destroySession(Request $request, string $id)
    {
        abort_if($id === $request->session()->getId(), 422, __('Utilisez « Se déconnecter » pour la session actuelle.'));
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', $id)->delete();
        UserDevice::where('user_id', $request->user()->id)->where('session_id', $id)->update(['session_id' => null]);
        AuditLogger::log('session.revoke', $request->user());

        return back()->with('status', __('Session déconnectée.'));
    }

    public function destroyOtherSessions(Request $request)
    {
        $request->validate(['password' => [$request->user()->password ? 'required' : 'nullable', 'current_password']]);
        DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        $request->user()->forceFill(['remember_token' => Str::random(60)])->save(); // invalide les cookies « se souvenir de moi »
        AuditLogger::log('session.revoke_all', $request->user());

        return back()->with('status', __('Toutes les autres sessions ont été déconnectées.'));
    }

    public function devices(Request $request)
    {
        $devices = $request->user()->devices()->orderByDesc('last_used_at')->get();

        return view('settings.devices', ['devices' => $devices, 'currentSession' => $request->session()->getId()]);
    }

    public function updateDevice(Request $request, UserDevice $device)
    {
        abort_unless($device->user_id === $request->user()->id, 403);
        $device->update($request->validate(['name' => ['required', 'string', 'max:100'], 'trusted' => ['nullable', 'boolean']]) + ['trusted' => $request->boolean('trusted')]);

        return back()->with('status', __('Appareil mis à jour.'));
    }

    public function revokeDevice(Request $request, UserDevice $device)
    {
        abort_unless($device->user_id === $request->user()->id, 403);
        if ($device->session_id) {
            DB::table('sessions')->where('id', $device->session_id)->delete();
        }
        $device->update(['revoked_at' => now()]);
        AuditLogger::log('device.revoke', $request->user(), ['device' => $device->name]);

        return back()->with('status', __('Appareil déconnecté.'));
    }

    // ---------------- Blocages & sanctions ----------------

    public function blocked(Request $request)
    {
        $blocks = Block::where('blocker_id', $request->user()->id)->with('blocked')->latest()->paginate(30);
        $muted = Mute::where('user_id', $request->user()->id)->with('muted')->latest()->get();

        return view('settings.blocked', compact('blocks', 'muted'));
    }

    public function sanctions(Request $request)
    {
        $sanctions = $request->user()->sanctions()->with('appeals')->latest()->get();

        return view('settings.sanctions', compact('sanctions'));
    }

    // ---------------- Données personnelles ----------------

    public function data(Request $request)
    {
        return view('settings.data', ['user' => $request->user()]);
    }

    public function export(Request $request, DataExportService $exporter)
    {
        $path = $exporter->export($request->user());
        AuditLogger::log('account.export', $request->user());

        return response()->download($path)->deleteFileAfterSend();
    }

    /** Suppression sécurisée : confirmation, déconnexion, purge définitive après le délai de grâce. */
    public function destroyAccount(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'password' => [$user->password ? 'required' : 'nullable', 'current_password'],
            'confirmation' => ['required', 'in:SUPPRIMER,DELETE,EFASE'],
        ]);
        $user->forceFill(['deletion_requested_at' => now()])->save();
        AuditLogger::log('account.delete_requested', $user);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', __('Votre compte sera supprimé définitivement dans :days jours. Reconnectez-vous pour annuler.', ['days' => config('vwajen.account_purge_days')]));
    }

    public function cancelDeletion(Request $request)
    {
        $request->user()->forceFill(['deletion_requested_at' => null])->save();
        AuditLogger::log('account.delete_cancelled', $request->user());

        return back()->with('status', __('Suppression annulée. Heureux de vous revoir !'));
    }

    // ---------------- Cookies & consentement ----------------

    public function cookies(Request $request)
    {
        return view('settings.cookies', ['user' => $request->user()]);
    }

    public function updateCookies(Request $request)
    {
        $prefs = ['necessary' => true, 'preferences' => $request->boolean('preferences'), 'analytics' => $request->boolean('analytics')];
        $request->user()->update(['cookie_prefs' => $prefs, 'cookie_consent_at' => now()]);

        return back()->with('status', __('Préférences de cookies enregistrées.'))->withCookie(cookie()->forever('cookie_consent', json_encode($prefs)));
    }

    // ---------------- API publique (clés développeur) ----------------

    public function apiKeys(Request $request)
    {
        return view('settings.api-keys', ['keys' => ApiKey::where('user_id', $request->user()->id)->latest()->get()]);
    }

    public function createApiKey(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        abort_if(ApiKey::where('user_id', $request->user()->id)->whereNull('revoked_at')->count() >= 5, 422, __('Maximum 5 clés actives.'));
        $plain = 'vwj_'.Str::random(40);
        ApiKey::create(['user_id' => $request->user()->id, 'name' => $data['name'], 'key_hash' => hash('sha256', $plain), 'prefix' => substr($plain, 0, 10)]);

        return back()->with('new_api_key', $plain);
    }

    public function revokeApiKey(Request $request, ApiKey $key)
    {
        abort_unless($key->user_id === $request->user()->id, 403);
        $key->update(['revoked_at' => now()]);

        return back()->with('status', __('Clé révoquée.'));
    }
}
