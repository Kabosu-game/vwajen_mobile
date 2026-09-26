<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Rôles & permissions ----
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('group', 40)->default('general');
            $table->timestamps();
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        // ---- Catégories (thèmes des programmes, centres d'intérêt) ----
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ht');
            $table->string('name_fr');
            $table->string('name_en');
            $table->string('icon', 40)->nullable();
            $table->string('color', 9)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ---- Graphe social ----
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('following_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 10)->default('accepted'); // accepted | pending
            $table->boolean('notify')->default(false);
            $table->timestamps();
            $table->unique(['follower_id', 'following_id']);
            $table->index(['following_id', 'status']);
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blocker_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['blocker_id', 'blocked_id']);
        });

        Schema::create('mutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muted_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'muted_id']);
        });

        // ---- Communautés (avant posts pour la clé étrangère) ----
        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('rules')->nullable();
            $table->string('avatar')->nullable();
            $table->string('cover')->nullable();
            $table->string('visibility', 10)->default('public'); // public | private
            $table->boolean('is_diaspora')->default(false);
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('members_count')->default(0);
            $table->boolean('is_hidden')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('community_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 12)->default('member'); // admin | moderator | member
            $table->string('status', 10)->default('approved'); // approved | pending | banned
            $table->timestamps();
            $table->unique(['community_id', 'user_id']);
        });

        Schema::create('community_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        // ---- Publications « Vwa » ----
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_of_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->string('link_title')->nullable();
            $table->string('link_description', 500)->nullable();
            $table->string('link_image', 2048)->nullable();
            $table->string('visibility', 12)->default('public'); // public | followers | community
            $table->string('lang', 5)->nullable();
            $table->string('country', 2)->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->string('hidden_reason')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['created_at']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('post_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // image | video
            $table->string('path');
            $table->string('thumbnail')->nullable();
            $table->string('alt')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('post_edits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->timestamps();
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->boolean('multiple')->default(false);
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('votes_count')->default(0);
            $table->timestamps();
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->unsignedInteger('votes_count')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('poll_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['poll_option_id', 'user_id']);
        });

        // ---- Interactions polymorphes ----
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_hidden')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('replies_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('likeable');
            $table->timestamps();
            $table->unique(['user_id', 'likeable_type', 'likeable_id']);
        });

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('bookmarkable');
            $table->timestamps();
            $table->unique(['user_id', 'bookmarkable_type', 'bookmarkable_id']);
        });

        Schema::create('reposts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('repostable');
            $table->timestamps();
            $table->unique(['user_id', 'repostable_type', 'repostable_id']);
            $table->index('created_at');
        });

        Schema::create('shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('shareable');
            $table->string('channel', 20); // internal | link | whatsapp | facebook | x | telegram | native | message
            $table->timestamps();
        });

        Schema::create('hidden_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('hideable');
            $table->timestamps();
            $table->unique(['user_id', 'hideable_type', 'hideable_id']);
        });

        Schema::create('hashtags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
        });

        Schema::create('hashtaggables', function (Blueprint $table) {
            $table->foreignId('hashtag_id')->constrained()->cascadeOnDelete();
            $table->morphs('hashtaggable');
            $table->timestamp('created_at')->nullable();
            $table->primary(['hashtag_id', 'hashtaggable_type', 'hashtaggable_id'], 'hashtaggables_pk');
        });

        Schema::create('hashtag_follows', function (Blueprint $table) {
            $table->foreignId('hashtag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['hashtag_id', 'user_id']);
        });

        Schema::create('mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('mentionable');
            $table->timestamps();
        });

        Schema::create('views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('viewable');
            $table->string('session_hash', 64)->nullable();
            $table->unsignedInteger('watch_seconds')->default(0);
            $table->timestamp('created_at')->nullable();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['views', 'mentions', 'hashtag_follows', 'hashtaggables', 'hashtags', 'hidden_contents', 'shares', 'reposts', 'bookmarks', 'likes', 'comments',
            'poll_votes', 'poll_options', 'polls', 'post_edits', 'post_media', 'posts', 'community_discussions', 'community_members', 'communities',
            'mutes', 'blocks', 'follows', 'categories', 'role_user', 'permission_role', 'permissions', 'roles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
