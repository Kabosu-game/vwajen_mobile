<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ---- Événements ----
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // organisateur
            $table->foreignId('community_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('cover')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('timezone', 64)->default('America/Port-au-Prince');
            $table->boolean('is_online')->default(false);
            $table->string('online_url', 2048)->nullable();
            $table->string('location_name')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('department', 40)->nullable();
            $table->string('country', 2)->default('HT');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('visibility', 12)->default('public');
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('going_count')->default(0);
            $table->unsignedInteger('interested_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['starts_at']);
            $table->index(['country', 'department', 'city']);
        });

        Schema::create('event_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 12); // going | interested | not_going
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        // Rappels génériques (événements, débats, lives)
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('remindable');
            $table->timestamp('remind_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'remindable_type', 'remindable_id', 'remind_at'], 'reminders_unique');
            $table->index(['remind_at', 'sent_at']);
        });

        // ---- Messagerie ----
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->default('direct'); // direct | group
            $table->string('name')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('cleared_at')->nullable(); // suppression de conversation côté utilisateur
            $table->boolean('muted')->default(false);
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body')->nullable();
            $table->string('media_type', 10)->nullable(); // image | video
            $table->string('media_path')->nullable();
            $table->nullableMorphs('shared'); // partage interne d'un contenu
            $table->boolean('is_hidden')->default(false);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });

        // ---- Modération ----
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->morphs('reportable');
            $table->foreignId('reported_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 30); // spam | harassment | hate | violence | misinformation | impersonation | nudity | illegal | fake_account | other
            $table->text('details')->nullable();
            $table->string('status', 12)->default('pending'); // pending | reviewing | resolved | dismissed
            $table->string('priority', 8)->default('normal'); // low | normal | high
            $table->decimal('ai_score', 4, 3)->nullable();
            $table->string('ai_label')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('resolution')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('sanctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // warning | suspension | ban | content_removal | content_hidden
            $table->nullableMorphs('content');
            $table->text('reason');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sanction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 12)->default('pending'); // pending | accepted | rejected
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // ---- Audit ----
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->nullableMorphs('subject');
            $table->json('data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['action', 'created_at']);
        });

        // ---- Notifications ----
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 500)->unique();
            $table->string('public_key')->nullable();
            $table->string('auth_token')->nullable();
            $table->string('content_encoding', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('system_announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('audience', 20)->default('all'); // all | candidates | verified | diaspora
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });

        // ---- Paramètres & langues ----
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->string('type', 10)->default('string');
            $table->string('group', 30)->default('general');
            $table->timestamps();
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5)->unique();
            $table->string('name');
            $table->string('native_name');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('translation_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5);
            $table->string('key', 500);
            $table->text('value');
            $table->timestamps();
            $table->unique(['locale', 'key']);
        });

        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->morphs('translatable');
            $table->string('field', 40);
            $table->string('locale', 5);
            $table->longText('value');
            $table->string('provider', 20)->default('ai');
            $table->timestamps();
            $table->unique(['translatable_type', 'translatable_id', 'field', 'locale'], 'content_translations_unique');
        });

        // ---- Futur : newsletter, ambassadeurs, API ----
        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('locale', 5)->default('ht');
            $table->string('token', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->longText('body');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });

        Schema::create('ambassadors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('city')->nullable();
            $table->string('department', 40)->nullable();
            $table->string('country', 2)->default('HT');
            $table->text('motivation');
            $table->string('status', 12)->default('pending'); // pending | approved | rejected
            $table->string('referral_code', 16)->unique();
            $table->unsignedInteger('referrals_count')->default(0);
            $table->timestamps();
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('key_hash', 64)->unique();
            $table->string('prefix', 12);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['api_keys', 'ambassadors', 'newsletters', 'newsletter_subscribers', 'content_translations', 'translation_overrides', 'languages', 'settings',
            'system_announcements', 'push_subscriptions', 'notifications', 'audit_logs', 'appeals', 'sanctions', 'reports',
            'messages', 'conversation_participants', 'conversations', 'reminders', 'event_rsvps', 'events'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
