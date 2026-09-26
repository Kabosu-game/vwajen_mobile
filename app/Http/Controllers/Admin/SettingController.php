<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SystemAnnouncement;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Http\Request;

/** Paramètres de la plateforme et notifications système. */
class SettingController extends Controller
{
    /** Définition des paramètres modifiables : clé => [type, groupe, défaut]. */
    public const DEFINITIONS = [
        'site_tagline' => ['string', 'general', 'Vwa sitwayen yo'],
        'registration_open' => ['bool', 'general', true],
        'maintenance_banner' => ['string', 'general', ''],
        'election_date' => ['string', 'general', ''],
        'require_email_verification' => ['bool', 'security', true],
        'limit_register_per_hour' => ['int', 'security', 5],
        'limit_posts_per_minute' => ['int', 'security', 5],
        'limit_posts_per_hour' => ['int', 'security', 60],
        'limit_comments_per_minute' => ['int', 'security', 10],
        'limit_messages_per_minute' => ['int', 'security', 30],
        'banned_words' => ['string', 'moderation', ''],
        'auto_hide_threshold' => ['int', 'moderation', 5],
        'lives_enabled' => ['bool', 'features', true],
        'shorts_enabled' => ['bool', 'features', true],
        'ai_features_enabled' => ['bool', 'features', true],
        'contact_email' => ['string', 'general', 'contact@vwajen.ht'],
    ];

    public function index()
    {
        $values = [];
        foreach (self::DEFINITIONS as $key => [$type, $group, $default]) {
            $values[$group][$key] = ['type' => $type, 'value' => Setting::get($key, $default)];
        }

        return view('admin.settings', compact('values'));
    }

    public function update(Request $request)
    {
        $changes = [];
        foreach (self::DEFINITIONS as $key => [$type, $group, $default]) {
            $value = match ($type) {
                'bool' => $request->boolean($key),
                'int' => (int) $request->input($key, $default),
                default => (string) $request->input($key, ''),
            };
            if (Setting::get($key, $default) !== $value) {
                $changes[$key] = $value;
            }
            Setting::put($key, $value, $type, $group);
        }
        AuditLogger::log('settings.update', null, $changes);

        return back()->with('status', __('Paramètres enregistrés.'));
    }

    public function announcements()
    {
        return view('admin.announcements', ['announcements' => SystemAnnouncement::with('user')->latest()->paginate(20)]);
    }

    /** Notification système à une audience. */
    public function announce(Request $request, Notifier $notifier)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1000'],
            'audience' => ['required', 'in:all,candidates,verified,diaspora,staff'],
            'url' => ['nullable', 'url'],
        ]);
        $users = User::where('status', 'active');
        match ($data['audience']) {
            'candidates' => $users->where('account_type', 'candidate'),
            'verified' => $users->where('is_verified', true),
            'diaspora' => $users->where('country', '!=', 'HT'),
            'staff' => $users->whereHas('roles'),
            default => null,
        };
        $announcement = SystemAnnouncement::create($data + ['user_id' => $request->user()->id]);
        $count = $notifier->broadcast($users, 'system', null, ':title — :body', ['title' => $data['title'], 'body' => $data['body']], $data['url'] ?? route('home'), $announcement);
        $announcement->update(['recipients_count' => $count]);
        AuditLogger::log('announcement.send', $announcement, ['recipients' => $count]);

        return back()->with('status', __('Notification envoyée à :n utilisateurs.', ['n' => $count]));
    }
}
