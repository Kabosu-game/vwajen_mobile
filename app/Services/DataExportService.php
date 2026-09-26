<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/** Export des données personnelles (archive ZIP : JSON + médias). */
class DataExportService
{
    public function export(User $user): string
    {
        $data = [
            'generated_at' => now()->toIso8601String(),
            'account' => $user->makeVisible(['phone'])->toArray(),
            'roles' => $user->roles()->pluck('name'),
            'candidate_profile' => $user->candidateProfile?->toArray(),
            'organization_profile' => $user->organizationProfile?->toArray(),
            'official_profile' => $user->officialProfile?->toArray(),
            'posts' => $user->posts()->withTrashed()->with('media', 'poll.options')->get()->toArray(),
            'comments' => DB::table('comments')->where('user_id', $user->id)->get(),
            'likes' => DB::table('likes')->where('user_id', $user->id)->get(),
            'bookmarks' => DB::table('bookmarks')->where('user_id', $user->id)->get(),
            'reposts' => DB::table('reposts')->where('user_id', $user->id)->get(),
            'videos' => $user->videos()->withTrashed()->get()->toArray(),
            'lives' => $user->lives()->get()->toArray(),
            'events' => $user->events()->get()->toArray(),
            'event_rsvps' => DB::table('event_rsvps')->where('user_id', $user->id)->get(),
            'questions' => $user->questionsAsked()->get()->toArray(),
            'answers' => $user->answers()->get()->toArray(),
            'programs' => $user->programs()->with('versions.proposals')->get()->toArray(),
            'following' => $user->following()->pluck('username'),
            'followers' => $user->followers()->pluck('username'),
            'blocked' => DB::table('blocks')->join('users', 'users.id', '=', 'blocks.blocked_id')->where('blocker_id', $user->id)->pluck('username'),
            'followed_hashtags' => $user->followedHashtags()->pluck('name'),
            'communities' => $user->communities()->get(['communities.id', 'name', 'slug'])->toArray(),
            'messages_sent' => DB::table('messages')->where('user_id', $user->id)->get(['conversation_id', 'body', 'media_path', 'created_at']),
            'notifications' => DB::table('notifications')->where('notifiable_type', 'user')->where('notifiable_id', $user->id)->get(),
            'devices' => $user->devices()->get()->toArray(),
            'sessions' => DB::table('sessions')->where('user_id', $user->id)->get(['ip_address', 'user_agent', 'last_activity']),
            'reports_made' => DB::table('reports')->where('reporter_id', $user->id)->get(['reportable_type', 'reportable_id', 'reason', 'status', 'created_at']),
            'sanctions' => $user->sanctions()->get()->toArray(),
            'verification_requests' => $user->verificationRequests()->get()->toArray(),
        ];

        $dir = storage_path('app/exports');
        @mkdir($dir, 0775, true);
        $zipPath = $dir.'/vwajen-'.$user->username.'-'.now()->format('Ymd-His').'.zip';

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('donnees.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->addFromString('LISEZMOI.txt', "Export Vwajèn de @{$user->username}\nGénéré le ".now()->toDateTimeString()."\n\ndonnees.json : toutes vos données.\nmedias/ : vos photos et vidéos.\n");

        $disk = Storage::disk('public');
        $files = array_filter(array_merge(
            [$user->avatar, $user->cover],
            $user->posts()->withTrashed()->with('media')->get()->flatMap(fn ($p) => $p->media->pluck('path'))->all(),
            $user->videos()->withTrashed()->pluck('path')->all(),
        ));
        foreach ($files as $f) {
            if (! str_starts_with($f, 'http') && $disk->exists($f)) {
                $zip->addFile($disk->path($f), 'medias/'.basename($f));
            }
        }
        $zip->close();

        return $zipPath;
    }
}
