<?php

namespace App\Services;

use App\Models\Hashtag;
use App\Models\Mention;
use App\Models\User;
use App\Support\TextParser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Synchronisation des hashtags et mentions d'un contenu + notifications de mention. */
class ContentService
{
    public function __construct(private Notifier $notifier) {}

    public function syncHashtags(Model $model, ?string $text): void
    {
        if (! method_exists($model, 'hashtags')) {
            return;
        }
        $ids = [];
        foreach (TextParser::hashtags($text) as $name) {
            $tag = Hashtag::firstOrCreate(['name' => $name]);
            if ($tag->is_blocked) {
                continue;
            }
            $ids[$tag->id] = ['created_at' => now()];
        }
        $changes = $model->hashtags()->sync($ids);
        if ($changes['attached']) {
            Hashtag::whereIn('id', $changes['attached'])->update(['uses_count' => DB::raw('uses_count + 1'), 'last_used_at' => now()]);
        }
        if ($changes['detached']) {
            Hashtag::whereIn('id', $changes['detached'])->where('uses_count', '>', 0)->decrement('uses_count');
        }
    }

    public function syncMentions(Model $model, ?string $text, User $author, ?string $url = null): void
    {
        $usernames = TextParser::mentions($text);
        $existing = Mention::where('mentionable_type', $model->getMorphClass())->where('mentionable_id', $model->getKey())->pluck('user_id')->all();
        $users = $usernames ? User::whereIn('username', $usernames)->where('status', 'active')->get() : collect();

        Mention::where('mentionable_type', $model->getMorphClass())->where('mentionable_id', $model->getKey())
            ->whereNotIn('user_id', $users->pluck('id'))->delete();

        foreach ($users as $user) {
            if (in_array($user->id, $existing, true)) {
                continue;
            }
            if (! $user->allowsInteraction('allow_mentions', $author)) {
                continue;
            }
            Mention::create(['user_id' => $user->id, 'mentionable_type' => $model->getMorphClass(), 'mentionable_id' => $model->getKey()]);
            $this->notifier->send($user, 'mention', $author, ':name vous a mentionné', ['name' => $author->name], $url ?? $model->url(), $model);
        }
    }

    public function syncAll(Model $model, ?string $text, User $author): void
    {
        $this->syncHashtags($model, $text);
        $this->syncMentions($model, $text, $author);
    }
}
