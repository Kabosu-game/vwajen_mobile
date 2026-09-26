<?php

namespace App\Support;

use App\Models\Answer;
use App\Models\CandidateProfile;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Comment;
use App\Models\Commitment;
use App\Models\Community;
use App\Models\Debate;
use App\Models\Discussion;
use App\Models\Election;
use App\Models\Event;
use App\Models\Live;
use App\Models\Message;
use App\Models\OfficialProfile;
use App\Models\Podcast;
use App\Models\PodcastEpisode;
use App\Models\Post;
use App\Models\Program;
use App\Models\Proposal;
use App\Models\PublicRecord;
use App\Models\Question;
use App\Models\Report;
use App\Models\Role;
use App\Models\Sanction;
use App\Models\Setting;
use App\Models\Source;
use App\Models\SystemAnnouncement;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\Video;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** Carte des types polymorphes (clé courte => modèle). */
class Morph
{
    public const MAP = [
        'user' => User::class,
        'post' => Post::class,
        'comment' => Comment::class,
        'video' => Video::class,
        'live' => Live::class,
        'debate' => Debate::class,
        'question' => Question::class,
        'answer' => Answer::class,
        'event' => Event::class,
        'community' => Community::class,
        'discussion' => Discussion::class,
        'message' => Message::class,
        'chat' => ChatMessage::class,
        'podcast' => Podcast::class,
        'episode' => PodcastEpisode::class,
        'program' => Program::class,
        'proposal' => Proposal::class,
        'candidate' => CandidateProfile::class,
        'official' => OfficialProfile::class,
        'commitment' => Commitment::class,
        'record' => PublicRecord::class,
        'source' => Source::class,
        'sanction' => Sanction::class,
        'report' => Report::class,
        'verification' => VerificationRequest::class,
        'role' => Role::class,
        'setting' => Setting::class,
        'category' => Category::class,
        'election' => Election::class,
        'announcement' => SystemAnnouncement::class,
    ];

    /** Types pouvant être likés / commentés / enregistrés / repostés / partagés. */
    public const INTERACTABLE = ['post', 'video', 'live', 'debate', 'question', 'answer', 'event', 'discussion'];

    /** Types pouvant être signalés. */
    public const REPORTABLE = ['user', 'post', 'comment', 'video', 'live', 'debate', 'question', 'answer', 'event', 'community', 'discussion', 'message', 'chat', 'podcast'];

    public static function classFor(string $type): ?string
    {
        return self::MAP[$type] ?? null;
    }

    public static function find(string $type, int|string $id, bool $withTrashed = false): ?Model
    {
        $class = self::classFor($type);
        if (! $class) {
            return null;
        }
        $query = $class::query();
        if ($withTrashed && in_array(SoftDeletes::class, class_uses_recursive($class), true)) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public static function findOrFail(string $type, int|string $id, array $allowed): Model
    {
        abort_unless(in_array($type, $allowed, true), 404);
        $model = self::find($type, $id);
        abort_unless($model, 404);

        return $model;
    }

    public const LABELS = [
        'user' => 'Utilisateur', 'post' => 'Publication', 'comment' => 'Commentaire', 'video' => 'Vidéo', 'live' => 'Live',
        'debate' => 'Débat', 'question' => 'Question', 'answer' => 'Réponse', 'event' => 'Événement', 'community' => 'Communauté',
        'discussion' => 'Discussion', 'message' => 'Message', 'chat' => 'Message de chat', 'podcast' => 'Podcast',
    ];

    /** Libellé source (non traduit) — à passer en paramètre de notification avec le préfixe « __: ». */
    public static function key(string $type): string
    {
        return self::LABELS[$type] ?? ucfirst($type);
    }

    public static function label(string $type): string
    {
        return __(self::key($type));
    }
}
