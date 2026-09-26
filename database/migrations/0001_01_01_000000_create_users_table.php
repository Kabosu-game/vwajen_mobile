<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username', 30)->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 30)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('phone_code')->nullable();
            $table->timestamp('phone_code_expires_at')->nullable();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('apple_id')->nullable()->unique();

            // Profil
            $table->string('account_type', 20)->default('personal'); // personal | candidate | organization | official
            $table->string('avatar')->nullable();
            $table->string('cover')->nullable();
            $table->text('bio')->nullable();
            $table->string('website')->nullable();
            $table->string('location')->nullable();
            $table->string('city')->nullable();
            $table->string('department', 40)->nullable();
            $table->string('country', 2)->default('HT');
            $table->boolean('is_diaspora')->default(false);
            $table->string('locale', 5)->default('ht');

            // Vérification
            $table->boolean('is_verified')->default(false);
            $table->string('verified_type', 20)->nullable(); // candidate | organization | public | official
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('verified_until')->nullable();

            // Statut & modération
            $table->string('status', 20)->default('active'); // active | suspended | banned
            $table->timestamp('suspended_until')->nullable();
            $table->string('status_reason')->nullable();

            // Confidentialité
            $table->boolean('is_private')->default(false);
            $table->string('allow_messages', 20)->default('everyone'); // everyone | following | nobody
            $table->string('allow_comments', 20)->default('everyone');
            $table->string('allow_mentions', 20)->default('everyone');
            $table->boolean('show_location')->default(true);
            $table->boolean('show_email')->default(false);
            $table->boolean('show_phone')->default(false);
            $table->boolean('show_political')->default(true);
            $table->boolean('searchable')->default(true);

            // Préférences
            $table->string('theme', 10)->default('system'); // system | light | dark
            $table->string('font_size', 10)->default('md'); // sm | md | lg | xl
            $table->boolean('high_contrast')->default(false);
            $table->boolean('data_saver')->default(false);
            $table->boolean('reduce_autoplay')->default(false);
            $table->boolean('reduce_motion')->default(false);
            $table->string('feed_mode', 20)->default('personalized');
            $table->json('interests')->nullable();
            $table->json('content_prefs')->nullable();
            $table->json('notification_prefs')->nullable();

            $table->timestamp('cookie_consent_at')->nullable();
            $table->json('cookie_prefs')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->string('registration_ip', 45)->nullable();
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->unsignedInteger('posts_count')->default(0);
            $table->rememberToken();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['account_type', 'is_verified']);
            $table->index(['country', 'department']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('fingerprint', 64);
            $table->string('name')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('session_id')->nullable();
            $table->boolean('trusted')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'fingerprint']);
        });

        Schema::create('user_activity_days', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->primary(['user_id', 'day']);
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_days');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
