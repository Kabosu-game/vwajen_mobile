<?php

use App\Models\Comment;
use App\Models\Community;
use App\Models\Debate;
use App\Models\Discussion;
use App\Models\Event;
use App\Models\Live;
use App\Models\Message;
use App\Models\Post;
use App\Models\Question;
use App\Models\Video;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Passage à la suppression définitive des contenus : les éléments déjà « supprimés » (corbeille)
 * sont effacés pour de bon avec leurs interactions et fichiers, puis la colonne deleted_at disparaît.
 */
return new class extends Migration
{
    private const MODELS = [Message::class, Comment::class, Discussion::class, Post::class, Video::class, Live::class, Debate::class, Question::class, Event::class, Community::class];

    public function up(): void
    {
        foreach (self::MODELS as $class) {
            $table = (new $class)->getTable();
            if (Schema::hasColumn($table, 'deleted_at')) {
                $class::whereNotNull('deleted_at')->orderByDesc('id')->get()->each->delete();
                Schema::table($table, fn (Blueprint $t) => $t->dropColumn('deleted_at'));
            }
        }
    }

    public function down(): void
    {
        foreach (self::MODELS as $class) {
            $table = (new $class)->getTable();
            if (! Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, fn (Blueprint $t) => $t->softDeletes());
            }
        }
    }
};
