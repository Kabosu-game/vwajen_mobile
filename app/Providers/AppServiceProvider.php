<?php

namespace App\Providers;

use App\Models\Language;
use App\Models\Setting;
use App\Services\AiService;
use App\Services\MediaService;
use App\Services\Notifier;
use App\Services\VideoProcessor;
use App\Support\Morph;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Apple\AppleExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ([AiService::class, Notifier::class, MediaService::class, VideoProcessor::class] as $s) {
            $this->app->singleton($s);
        }
    }

    public function boot(): void
    {
        Relation::enforceMorphMap(Morph::MAP);
        Paginator::defaultView('partials.pagination');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Super-admin : toutes les permissions ; sinon permissions issues des rôles.
        Gate::before(fn ($user, string $ability) => $user->hasPermission($ability) ? true : null);

        $this->rateLimits();

        // Langues gérées depuis l'administration (activation, ajout) : elles alimentent les sélecteurs de langue.
        try {
            $languages = Cache::remember('languages.active', 600, fn () => Language::where('is_active', true)
                ->orderBy('position')->get(['code', 'native_name'])->toArray());
            if ($languages) {
                config(['vwajen.locales' => collect($languages)->mapWithKeys(fn ($l) => [$l['code'] => ['name' => $l['native_name'], 'short' => strtoupper($l['code'])]])->all()]);
            }
            $default = Cache::remember('languages.default', 600, fn () => Language::where('is_default', true)->value('code'));
            if ($default) {
                config(['app.locale' => $default]);
            }
        } catch (\Throwable) {
            // base non migrée : on garde la configuration par défaut
        }

        Event::listen(SocialiteWasCalled::class, [AppleExtendSocialite::class, 'handle']);

        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->count() : 0);
            $view->with('unreadMessages', $user ? $user->unreadMessagesCount() : 0);
        });
    }

    /** Rate limiting (sécurité / anti-spam). Valeurs ajustables dans les paramètres d'administration. */
    private function rateLimits(): void
    {
        $key = fn (Request $r) => $r->user()?->id ?: $r->ip();

        RateLimiter::for('login', fn (Request $r) => [
            Limit::perMinute(5)->by(strtolower((string) $r->input('login')).'|'.$r->ip()),
            Limit::perMinute(20)->by($r->ip()),
        ]);
        RateLimiter::for('register', fn (Request $r) => Limit::perHour((int) Setting::get('limit_register_per_hour', 5))->by($r->ip()));
        RateLimiter::for('password', fn (Request $r) => Limit::perMinute(3)->by($r->ip()));
        RateLimiter::for('sms', fn (Request $r) => [Limit::perMinute(1)->by($key($r)), Limit::perDay(10)->by($key($r))]);
        RateLimiter::for('publish', fn (Request $r) => [
            Limit::perMinute((int) Setting::get('limit_posts_per_minute', 5))->by($key($r)),
            Limit::perHour((int) Setting::get('limit_posts_per_hour', 60))->by($key($r)),
        ]);
        RateLimiter::for('interact', fn (Request $r) => Limit::perMinute(120)->by($key($r)));
        RateLimiter::for('comment', fn (Request $r) => Limit::perMinute((int) Setting::get('limit_comments_per_minute', 10))->by($key($r)));
        RateLimiter::for('message', fn (Request $r) => Limit::perMinute((int) Setting::get('limit_messages_per_minute', 30))->by($key($r)));
        RateLimiter::for('chat', fn (Request $r) => Limit::perMinute(30)->by($key($r)));
        RateLimiter::for('report', fn (Request $r) => Limit::perHour(30)->by($key($r)));
        RateLimiter::for('follow', fn (Request $r) => Limit::perHour(200)->by($key($r)));
        RateLimiter::for('upload', fn (Request $r) => Limit::perMinute(600)->by($key($r)));
        RateLimiter::for('signal', fn (Request $r) => Limit::perMinute(600)->by($key($r)));
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(60)->by($key($r)));
        RateLimiter::for('api', fn (Request $r) => $r->attributes->get('api_key')
            ? Limit::perMinute(600)->by('k'.$r->attributes->get('api_key')->id)
            : Limit::perMinute(60)->by($r->ip()));
    }
}
