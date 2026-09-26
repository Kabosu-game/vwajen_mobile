<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Suppression définitive d'un contenu : rien n'est conservé ni restaurable.
 * Appelé automatiquement avant chaque suppression (trait PurgesCompletely) : efface les interactions
 * polymorphes (que les clés étrangères ne couvrent pas), les contenus enfants et les fichiers.
 */
class ContentPurger
{
    /** Tables polymorphes rattachées à un contenu : table => préfixe de colonnes. */
    private const MORPHS = [
        'likes' => 'likeable', 'bookmarks' => 'bookmarkable', 'reposts' => 'repostable', 'shares' => 'shareable',
        'hidden_contents' => 'hideable', 'hashtaggables' => 'hashtaggable', 'mentions' => 'mentionable', 'views' => 'viewable',
        'reminders' => 'remindable', 'content_translations' => 'translatable', 'chat_messages' => 'chatable', 'sources' => 'sourceable',
    ];

    /** Colonnes contenant un fichier du disque public. */
    private const FILE_COLUMNS = ['avatar', 'cover', 'path', 'thumbnail', 'media_path', 'audio_path'];

    public function __construct(private MediaService $media) {}

    public function purge(Model $content): void
    {
        $type = $content->getMorphClass();
        $id = $content->getKey();
        $files = $this->filesOf($content);

        // Contenus enfants supprimés un par un pour qu'ils soient eux aussi purgés (fichiers, likes…).
        foreach ($this->children($content) as $child) {
            $child->delete();
        }
        // Réponses avant les commentaires parents (ordre inverse de création).
        Comment::where('commentable_type', $type)->where('commentable_id', $id)->orderByDesc('id')->get()->each->delete();

        foreach (Document::where('documentable_type', $type)->where('documentable_id', $id)->get() as $doc) {
            $files[] = $doc->path;
            $doc->delete();
        }
        foreach (self::MORPHS as $table => $morph) {
            DB::table($table)->where($morph.'_type', $type)->where($morph.'_id', $id)->delete();
        }

        // Références restantes : partages en message, replays, notifications, signalements en attente.
        DB::table('messages')->where('shared_type', $type)->where('shared_id', $id)->update(['shared_type' => null, 'shared_id' => null]);
        DB::table('videos')->where('source_type', $type)->where('source_id', $id)->update(['source_type' => null, 'source_id' => null]);
        DB::table('notifications')->where('data->subject_type', $type)->where('data->subject_id', $id)->delete();
        DB::table('reports')->where('reportable_type', $type)->where('reportable_id', $id)->whereIn('status', ['pending', 'reviewing'])
            ->update(['status' => 'resolved', 'handled_at' => now(), 'resolution' => 'deleted', 'updated_at' => now()]);

        // Les fichiers ne sont effacés qu'une fois la suppression validée en base.
        DB::afterCommit(fn () => $this->media->delete(...array_values(array_unique(array_filter($files)))));
    }

    /** @return iterable<Model> */
    private function children(Model $content): iterable
    {
        return match ($content->getMorphClass()) {
            'community' => $content->posts()->get()->concat($content->discussions()->get())->concat($content->events()->get()),
            'question' => $content->answers()->get(),
            'comment' => Comment::where('parent_id', $content->getKey())->get(),
            default => [],
        };
    }

    /** @return list<string> */
    private function filesOf(Model $content): array
    {
        $raw = $content->getAttributes();
        $files = array_values(array_filter(array_intersect_key($raw, array_flip(self::FILE_COLUMNS)), 'is_string'));

        if (! empty($raw['qualities'])) {
            $files = array_merge($files, array_values((array) json_decode($raw['qualities'], true)));
        }

        return array_merge($files, match ($content->getMorphClass()) {
            'post' => $content->media()->get()->flatMap(fn ($m) => [$m->path, $m->thumbnail])->all(),
            'video' => $content->subtitles()->pluck('path')->all(),
            'podcast' => $content->episodes()->pluck('audio_path')->all(),
            default => [],
        });
    }
}
