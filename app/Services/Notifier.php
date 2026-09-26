<?php

namespace App\Services;

use App\Models\Follow;
use App\Models\User;
use App\Notifications\ActivityNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Point d'entrée unique pour notifier (respect des blocages, de l'auteur et des préférences). */
class Notifier
{
    /**
     * @param  User|iterable<User>|null  $recipients
     */
    public function send($recipients, string $type, ?User $actor, string $textKey, array $params = [], ?string $url = null, ?Model $subject = null): void
    {
        $list = $recipients instanceof User ? collect([$recipients]) : collect($recipients)->filter();

        $list->unique('id')->each(function (User $user) use ($type, $actor, $textKey, $params, $url, $subject) {
            if ($actor && ($user->id === $actor->id || $user->isBlockedBetween($actor))) {
                return;
            }
            if (! $user->isActive() && ! in_array($type, ActivityNotification::FORCED, true)) {
                return;
            }
            $user->notify(new ActivityNotification(
                $type, $actor, $textKey, $params, $url, $subject?->getMorphClass(), $subject?->getKey(),
            ));
        });
    }

    /** Diffusion à un grand nombre d'utilisateurs, par lots. */
    public function broadcast(Builder $users, string $type, ?User $actor, string $textKey, array $params = [], ?string $url = null, ?Model $subject = null): int
    {
        $count = 0;
        $users->chunkById(200, function (Collection $chunk) use (&$count, $type, $actor, $textKey, $params, $url, $subject) {
            $this->send($chunk, $type, $actor, $textKey, $params, $url, $subject);
            $count += $chunk->count();
        });

        return $count;
    }

    public function followersOf(User $user)
    {
        return User::whereIn('id', Follow::where('following_id', $user->id)->where('status', 'accepted')->select('follower_id'));
    }
}
