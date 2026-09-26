<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;

/** Gestion des appareils : enregistre l'appareil utilisé à chaque connexion. */
class DeviceTracker
{
    public function track(User $user, Request $request): UserDevice
    {
        $ua = (string) $request->userAgent();
        $fingerprint = hash('sha256', $ua.'|'.$request->cookie('vw_device', ''));
        [$platform, $browser] = self::parse($ua);

        $device = UserDevice::firstOrNew(['user_id' => $user->id, 'fingerprint' => $fingerprint]);
        $isNew = ! $device->exists;
        $device->fill([
            'name' => $device->name ?: trim("$browser — $platform"),
            'platform' => $platform,
            'browser' => $browser,
            'ip_address' => $request->ip(),
            'session_id' => $request->session()->getId(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ])->save();

        if ($isNew && $user->devices()->count() > 1) {
            app(Notifier::class)->send($user, 'system', null, 'Nouvelle connexion depuis :device', ['device' => $device->name], route('settings.devices'));
        }

        return $device;
    }

    public static function parse(string $ua): array
    {
        $platform = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => __('Inconnu'),
        };
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari',
            default => __('Navigateur'),
        };

        return [$platform, $browser];
    }
}
