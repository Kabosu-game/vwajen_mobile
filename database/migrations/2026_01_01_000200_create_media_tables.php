<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 10)->default('long'); // short | long | replay
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('path');
            $table->string('thumbnail')->nullable();
            $table->json('qualities')->nullable(); // {"360": "path", "720": "path"}
            $table->unsignedInteger('duration')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('processing_status', 12)->default('ready'); // pending | processing | ready | failed
            $table->string('visibility', 12)->default('public');
            $table->string('lang', 5)->nullable();
            $table->string('country', 2)->nullable();
            $table->nullableMorphs('source'); // live / debate replay
            $table->longText('transcript')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['kind', 'created_at']);
        });

        Schema::create('video_subtitles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->string('lang', 5);
            $table->string('label');
            $table->string('path');
            $table->boolean('is_auto')->default(false);
            $table->timestamps();
        });

        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('received')->default(0);
            $table->string('status', 12)->default('uploading'); // uploading | complete | used
            $table->string('path')->nullable();
            $table->timestamps();
        });

        Schema::create('lives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 10)->default('video'); // video | audio (Audio Spaces)
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->string('status', 12)->default('scheduled'); // scheduled | live | ended | cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('stream_key', 64)->unique();
            $table->string('playback_url', 2048)->nullable(); // HLS externe (OBS, serveur média)
            $table->foreignId('replay_video_id')->nullable()->constrained('videos')->nullOnDelete();
            $table->boolean('chat_enabled')->default(true);
            $table->boolean('chat_followers_only')->default(false);
            $table->unsignedSmallInteger('slow_mode_seconds')->default(0);
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('peak_viewers')->default(0);
            $table->unsignedInteger('total_viewers')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->longText('transcript')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('live_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 12)->default('guest'); // host | guest | moderator
            $table->string('status', 10)->default('invited'); // invited | accepted | declined | removed
            $table->timestamps();
            $table->unique(['live_id', 'user_id']);
        });

        Schema::create('live_viewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('peer_id', 64);
            $table->boolean('is_publisher')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('kicked_at')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->timestamps();
            $table->unique(['live_id', 'peer_id']);
        });

        Schema::create('live_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('emoji', 16);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('live_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_id')->constrained()->cascadeOnDelete();
            $table->string('from_peer', 64);
            $table->string('to_peer', 64);
            $table->string('type', 12); // offer | answer | candidate | bye
            $table->longText('payload');
            $table->timestamp('created_at')->nullable();
            $table->index(['live_id', 'to_peer', 'id']);
        });

        Schema::create('debates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // créateur
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('live_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->string('cover')->nullable();
            $table->string('status', 12)->default('scheduled'); // scheduled | live | ended | cancelled
            $table->timestamp('scheduled_at');
            $table->unsignedSmallInteger('duration_minutes')->default(90);
            $table->string('constituency')->nullable();
            $table->string('playback_url', 2048)->nullable();
            $table->foreignId('replay_video_id')->nullable()->constrained('videos')->nullOnDelete();
            $table->boolean('public_questions')->default(true);
            $table->boolean('chat_enabled')->default(true);
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->longText('summary')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('debate_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 10)->default('invited'); // invited | accepted | declined
            $table->unsignedSmallInteger('speaking_order')->default(0);
            $table->timestamps();
            $table->unique(['debate_id', 'user_id']);
        });

        Schema::create('debate_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->string('status', 10)->default('pending'); // pending | selected | answered | rejected
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();
        });

        Schema::create('debate_question_votes', function (Blueprint $table) {
            $table->foreignId('debate_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['debate_question_id', 'user_id']);
        });

        // Chat en direct (lives & débats)
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->morphs('chatable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 500);
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->index(['chatable_type', 'chatable_id', 'id']);
        });

        // Podcasts / streaming audio
        Schema::create('podcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lang', 5)->default('ht');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });

        Schema::create('podcast_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('podcast_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('audio_path');
            $table->unsignedInteger('duration')->nullable();
            $table->longText('transcript')->nullable();
            $table->unsignedInteger('plays_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['podcast_episodes', 'podcasts', 'chat_messages', 'debate_question_votes', 'debate_questions', 'debate_participants', 'debates',
            'live_signals', 'live_reactions', 'live_viewers', 'live_participants', 'lives', 'uploads', 'video_subtitles', 'videos'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
