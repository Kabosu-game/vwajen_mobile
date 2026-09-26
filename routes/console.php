<?php

use App\Models\Debate;
use App\Models\Follow;
use App\Models\Live;
use App\Models\LiveSignal;
use App\Models\LiveViewer;
use App\Models\Reminder;
use App\Models\Role;
use App\Models\Upload;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\AuditLogger;
use App\Services\Notifier;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Minishlink\WebPush\VAPID;

/*
| Tâches planifiées de Vwajèn. Lancer le planificateur : php artisan schedule:work (dev) ou cron « schedule:run » chaque minute.
*/

// Rappels (événements, débats, lives) + notifications avant les débats.
Artisan::command('vwajen:reminders', function (Notifier $notifier) {
    $due = Reminder::whereNull('sent_at')->where('remind_at', '<=', now())->with(['user', 'remindable'])->limit(1000)->get();
    foreach ($due as $r) {
        $subject = $r->remindable;
        if ($subject && $r->user) {
            $start = $subject->starts_at ?? $subject->scheduled_at;
            if ($start && $start->isFuture()) {
                $notifier->send($r->user, $r->remindable_type === 'event' ? 'event' : ($r->remindable_type === 'debate' ? 'debate' : 'live'), null,
                    'Rappel : « :title » commence :when', ['title' => $subject->title, 'when' => $start->diffForHumans()], $subject->url(), $subject);
            }
        }
        $r->update(['sent_at' => now()]);
    }

    // Notifications automatiques 1 h avant chaque débat, aux abonnés des candidats participants.
    $debates = Debate::where('status', 'scheduled')->whereNull('reminder_sent_at')->whereBetween('scheduled_at', [now(), now()->addHour()])->get();
    foreach ($debates as $debate) {
        $participantIds = $debate->participants()->where('status', 'accepted')->pluck('user_id');
        $audience = User::whereIn('id', Follow::whereIn('following_id', $participantIds)->where('status', 'accepted')->select('follower_id'))
            ->orWhereIn('id', $participantIds);
        $notifier->broadcast($audience, 'debate', null, 'Le débat « :title » commence dans moins d\'une heure', ['title' => $debate->title], $debate->url(), $debate);
        $debate->update(['reminder_sent_at' => now()]);
    }
    $this->info($due->count().' rappels, '.$debates->count().' débats notifiés.');
})->purpose('Envoie les rappels et notifications avant débats/événements/lives');

// Expiration des vérifications.
Artisan::command('vwajen:expire-verifications', function (Notifier $notifier) {
    $users = User::where('is_verified', true)->whereNotNull('verified_until')->where('verified_until', '<', now())->get();
    foreach ($users as $u) {
        $u->forceFill(['is_verified' => false])->save();
        VerificationRequest::where('user_id', $u->id)->where('status', 'approved')->update(['status' => 'expired']);
        $notifier->send($u, 'system', null, 'Votre badge vérifié a expiré. Vous pouvez faire une nouvelle demande.', [], route('verification.index'));
    }
    // Avertissement 14 jours avant.
    User::where('is_verified', true)->whereBetween('verified_until', [now()->addDays(14)->startOfDay(), now()->addDays(14)->endOfDay()])->each(
        fn ($u) => $notifier->send($u, 'system', null, 'Votre badge vérifié expire le :date', ['date' => $u->verified_until->format('d/m/Y')], route('verification.index'))
    );
    $this->info($users->count().' vérifications expirées.');
})->purpose('Retire les badges expirés');

// Levée automatique des suspensions arrivées à échéance.
Artisan::command('vwajen:lift-suspensions', function () {
    $n = User::where('status', 'suspended')->whereNotNull('suspended_until')->where('suspended_until', '<', now())
        ->update(['status' => 'active', 'suspended_until' => null, 'status_reason' => null]);
    $this->info("$n suspensions levées.");
});

// Suppression sécurisée des comptes après le délai de grâce : anonymisation + suppression des médias + suppression définitive.
Artisan::command('vwajen:purge-accounts', function () {
    $users = User::whereNotNull('deletion_requested_at')->where('deletion_requested_at', '<', now()->subDays(config('vwajen.account_purge_days')))->get();
    foreach ($users as $user) {
        DB::transaction(function () use ($user) {
            $disk = Storage::disk('public');
            foreach (array_filter([$user->avatar, $user->cover]) as $f) {
                $disk->delete($f);
            }
            // Chaque contenu est supprimé via son modèle pour effacer aussi ses fichiers et interactions (ContentPurger).
            foreach (App\Support\Morph::MAP as $class) {
                if (in_array(App\Models\Concerns\PurgesCompletely::class, class_uses_recursive($class), true)) {
                    $owner = $class === App\Models\Community::class ? 'owner_id' : 'user_id';
                    $class::where($owner, $user->id)->orderByDesc('id')->get()->each->delete();
                }
            }
            Storage::disk('local')->deleteDirectory('verifications/'.$user->id);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            AuditLogger::log('account.purged', null, ['user_id' => $user->id]);
            $user->forceDelete(); // les contenus liés sont supprimés en cascade
        });
    }
    $this->info($users->count().' comptes supprimés définitivement.');
})->purpose('Suppression définitive des comptes après le délai de grâce');

// Nettoyage : signalisation WebRTC, spectateurs inactifs, uploads abandonnés, exports.
Artisan::command('vwajen:cleanup', function () {
    LiveSignal::where('created_at', '<', now()->subMinutes(10))->delete();
    Live::where('status', 'live')->where('started_at', '<', now()->subHours(12))
        ->whereDoesntHave('viewers', fn ($q) => $q->where('is_publisher', true)->where('last_seen_at', '>=', now()->subMinutes(5)))
        ->update(['status' => 'ended', 'ended_at' => now()]);
    LiveViewer::where('last_seen_at', '<', now()->subDays(2))->whereNull('kicked_at')->where('is_banned', false)->delete();
    foreach (Upload::where('status', 'uploading')->where('updated_at', '<', now()->subDays(2))->get() as $u) {
        Storage::disk('local')->delete($u->path);
        $u->delete();
    }
    foreach (glob(storage_path('app/exports/*.zip')) ?: [] as $f) {
        if (filemtime($f) < time() - 86400) {
            @unlink($f);
        }
    }
    $this->info('Nettoyage terminé.');
});

// Génère les clés VAPID pour les notifications push.
Artisan::command('vwajen:vapid', function () {
    $keys = VAPID::createVapidKeys();
    $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
    $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
    $this->info('Copiez ces lignes dans votre fichier .env');
})->purpose('Génère les clés Web Push');

// Crée un super-administrateur.
Artisan::command('vwajen:admin {email} {--password=} {--name=Administrateur} {--username=admin_vwajen}', function (string $email) {
    $password = $this->option('password') ?: Str::password(16);
    $user = User::firstOrNew(['email' => strtolower($email)]);
    $user->fill(['name' => $this->option('name'), 'username' => $user->username ?? $this->option('username'), 'password' => $password, 'locale' => $user->locale ?? 'fr']);
    $user->forceFill(['email_verified_at' => now(), 'is_verified' => true, 'verified_type' => 'public'])->save();
    $user->roles()->syncWithoutDetaching([Role::where('name', 'superadmin')->value('id')]);
    $this->info("Super-admin : {$user->email} / mot de passe : {$password}");
})->purpose('Crée ou promeut un super-administrateur');

Schedule::command('vwajen:reminders')->everyMinute()->withoutOverlapping();
Schedule::command('vwajen:lift-suspensions')->everyFiveMinutes();
Schedule::command('vwajen:expire-verifications')->daily();
Schedule::command('vwajen:purge-accounts')->dailyAt('03:00');
Schedule::command('vwajen:cleanup')->hourly();
