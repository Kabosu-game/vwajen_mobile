<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('photo')->nullable();
            $table->text('biography')->nullable();
            $table->longText('career')->nullable(); // parcours
            $table->string('party')->nullable(); // affiliation politique déclarée
            $table->string('position_sought'); // poste recherché
            $table->string('constituency')->nullable(); // circonscription
            $table->string('department', 40)->nullable();
            $table->string('city')->nullable();
            $table->unsignedSmallInteger('election_year')->nullable();
            $table->string('website')->nullable();
            $table->string('status', 12)->default('pending'); // pending | verified | rejected
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('organization_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('legal_name');
            $table->string('org_type', 30); // party | ngo | media | government | association | company | other
            $table->string('registration_number')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('is_professional')->default(false);
            $table->string('status', 12)->default('pending');
            $table->timestamps();
        });

        Schema::create('official_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('office'); // fonction élue
            $table->string('institution')->nullable();
            $table->string('constituency')->nullable();
            $table->string('department', 40)->nullable();
            $table->string('party')->nullable();
            $table->date('mandate_start')->nullable();
            $table->date('mandate_end')->nullable();
            $table->text('biography')->nullable();
            $table->timestamps();
        });

        // Historique des modifications importantes (candidats, programmes, élus)
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('subject');
            $table->string('field');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // ---- Programmes ----
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('status', 12)->default('draft'); // draft | published | archived
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });

        Schema::create('program_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version_number');
            $table->string('title');
            $table->longText('summary')->nullable();
            $table->text('changelog')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->unique(['program_id', 'version_number']);
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_version_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('description');
            $table->string('timeline')->nullable();
            $table->string('budget')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('documentable');
            $table->string('title');
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->date('published_on')->nullable();
            $table->timestamps();
        });

        // ---- Sources ----
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('sourceable');
            $table->string('name');
            $table->string('url', 2048)->nullable();
            $table->date('published_on')->nullable();
            $table->string('status', 12)->default('unverified'); // provided | verified | unverified | disputed
            $table->boolean('provided_by_candidate')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('source_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 12)->nullable();
            $table->string('to_status', 12);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // ---- Kesyon pou kandida yo ----
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained('users')->nullOnDelete(); // candidat / élu ciblé
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('status', 10)->default('open'); // open | answered | closed
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('close_reason')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('supports_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->unsignedInteger('answers_count')->default(0);
            $table->unsignedInteger('shares_count')->default(0);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('reposts_count')->default(0);
            $table->unsignedInteger('bookmarks_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'supports_count']);
        });

        Schema::create('question_supports', function (Blueprint $table) {
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['question_id', 'user_id']);
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('body')->nullable();
            $table->foreignId('video_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_hidden')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();
        });

        // ---- Suivi des responsables publics ----
        Schema::create('public_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20); // activity | declaration | document | vote | appointment
            $table->string('title');
            $table->longText('body')->nullable();
            $table->date('occurred_on')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });

        Schema::create('commitments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 15)->default('not_started'); // not_started | in_progress | fulfilled | partially | not_fulfilled
            $table->date('made_on')->nullable();
            $table->date('due_on')->nullable();
            $table->timestamps();
        });

        Schema::create('commitment_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commitment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 15);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        // ---- Vérification ----
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // candidate | organization | public | official
            $table->text('message')->nullable();
            $table->string('status', 12)->default('pending'); // pending | approved | rejected | expired | cancelled
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('verification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('path'); // disque privé
            $table->string('mime', 100)->nullable();
            $table->timestamps();
        });

        // ---- Élections (archives, résultats, suivi post-électoral) ----
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20); // presidential | legislative | municipal | local | referendum
            $table->date('held_on');
            $table->unsignedTinyInteger('round')->default(1);
            $table->text('description')->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->boolean('results_published')->default(false);
            $table->timestamps();
        });

        Schema::create('election_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('election_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('candidate_name');
            $table->string('party')->nullable();
            $table->string('constituency')->nullable();
            $table->string('department', 40)->nullable();
            $table->unsignedBigInteger('votes')->default(0);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('elected')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['election_results', 'elections', 'verification_documents', 'verification_requests', 'commitment_updates', 'commitments', 'public_records',
            'answers', 'question_supports', 'questions', 'source_verifications', 'sources', 'documents', 'proposals', 'program_versions', 'programs',
            'change_logs', 'official_profiles', 'organization_profiles', 'candidate_profiles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
