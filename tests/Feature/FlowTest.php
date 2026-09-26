<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\Conversation;
use App\Models\Event;
use App\Models\Follow;
use App\Models\Live;
use App\Models\Post;
use App\Models\Program;
use App\Models\Question;
use App\Models\Report;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Parcours fonctionnels principaux (actions POST). */
class FlowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function user(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    public function test_registration_login_and_logout(): void
    {
        $this->post('/register', [
            'name' => 'Test Sitwayen', 'username' => 'test_sitwayen', 'email' => 'test@example.org', 'password' => 'Secret123',
            'password_confirmation' => 'Secret123', 'account_type' => 'personal', 'country' => 'HT', 'terms' => 1, '_form_ts' => time() - 10,
        ])->assertRedirect(route('settings.interests'));
        $this->assertAuthenticated();
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/login', ['login' => 'test_sitwayen', 'password' => 'Secret123'])->assertRedirect(route('home'));
        $this->assertAuthenticated();

        // Pot de miel anti-spam
        $this->post('/logout');
        $this->post('/register', ['name' => 'Bot', 'username' => 'bot1', 'email' => 'bot@example.org', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
            'account_type' => 'personal', 'country' => 'HT', 'terms' => 1, 'website_url' => 'http://spam'])->assertSessionHasErrors('email');
        // E-mail jetable refusé
        $this->post('/register', ['name' => 'Fake', 'username' => 'fake1', 'email' => 'x@mailinator.com', 'password' => 'Secret123', 'password_confirmation' => 'Secret123',
            'account_type' => 'personal', 'country' => 'HT', 'terms' => 1, '_form_ts' => time() - 10])->assertSessionHasErrors('email');
    }

    public function test_post_like_comment_repost_bookmark_follow(): void
    {
        Storage::fake('public');
        $me = $this->user('jeanmarc');
        $other = $this->user('nadege_l');
        Follow::where('follower_id', $other->id)->where('following_id', $me->id)->delete(); // données de démo aléatoires
        $this->actingAs($me);

        $this->post('/posts', [
            'body' => 'Bonjou #Ayiti @nadege_l https://example.org', 'images' => [UploadedFile::fake()->image('a.jpg', 1200, 800)],
            'alts' => ['Une image'], 'poll_options' => ['Wi', 'Non', '', ''], 'poll_hours' => 24,
        ])->assertRedirect();
        $post = Post::latest('id')->first();
        $this->assertSame(1, $post->media()->count());
        $this->assertNotNull($post->poll);
        $this->assertTrue($post->hashtags()->where('name', 'ayiti')->exists());
        $this->assertDatabaseHas('mentions', ['user_id' => $other->id, 'mentionable_id' => $post->id]);

        $this->actingAs($other);
        $this->postJson("/i/post/{$post->id}/like")->assertJson(['active' => true, 'count' => 1]);
        $this->postJson("/i/post/{$post->id}/comments", ['body' => 'Dakò!'])->assertOk()->assertJsonStructure(['html']);
        $this->postJson("/i/post/{$post->id}/repost")->assertJson(['active' => true]);
        $this->postJson("/i/post/{$post->id}/bookmark")->assertJson(['active' => true]);
        $this->postJson("/i/post/{$post->id}/share", ['channel' => 'whatsapp'])->assertOk();
        $this->postJson("/polls/{$post->poll->id}/vote", ['options' => [$post->poll->options->first()->id]])->assertOk();
        $this->postJson("/users/{$me->username}/follow")->assertJson(['state' => 'accepted']);
        $this->assertTrue($me->fresh()->notifications()->count() >= 4);

        $post->refresh();
        $this->assertSame(1, $post->likes_count);
        $this->assertSame(1, $post->comments_count);
        $this->assertSame(1, $post->reposts_count);

        // Modification + historique, puis suppression par l'auteur
        $this->actingAs($me)->put("/v/{$post->id}", ['body' => 'Bonjou modifié'])->assertRedirect();
        $this->assertNotNull($post->fresh()->edited_at);
        $this->assertSame(1, $post->edits()->count());
        $this->deleteJson("/v/{$post->id}")->assertJson(['deleted' => true]);
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_block_hides_content_and_prevents_messages(): void
    {
        $a = $this->user('jeanmarc');
        $b = $this->user('wilnerj');
        $this->actingAs($a)->postJson("/users/{$b->username}/block")->assertJson(['blocked' => true]);
        $this->actingAs($b)->get("/messages/new?user={$a->username}")->assertForbidden();
    }

    public function test_question_answer_flow(): void
    {
        $citizen = $this->user('jeanmarc');
        $candidate = $this->user('mariange_t');
        $this->actingAs($citizen)->post('/questions', ['title' => 'Ki plan ou pou elektrisite nan Sid la?', 'candidate_id' => $candidate->id])->assertRedirect();
        $q = Question::latest('id')->first();
        $this->assertTrue($candidate->notifications()->where('data', 'like', '%question%')->exists());

        $this->actingAs($this->user('nadege_l'))->postJson("/questions/{$q->id}/support")->assertJson(['active' => true]);
        $this->actingAs($this->user('nadege_l'))->post("/questions/{$q->id}/answers", ['body' => 'Non'])->assertForbidden();
        $this->actingAs($candidate)->post("/questions/{$q->id}/answers", ['body' => 'Nou gen yon plan solè.'])->assertRedirect();
        $this->assertSame('answered', $q->fresh()->status);
        $this->assertTrue($citizen->notifications()->where('data', 'like', '%candidate_answer%')->exists());
        $this->actingAs($citizen)->post("/questions/{$q->id}/close")->assertRedirect();
        $this->assertSame('closed', $q->fresh()->status);
    }

    public function test_report_and_admin_moderation(): void
    {
        $post = Post::where('user_id', $this->user('wilnerj')->id)->first();
        $this->actingAs($this->user('jeanmarc'))->post("/report/post/{$post->id}", ['reason' => 'spam', 'details' => 'Spam'])->assertRedirect();
        $report = Report::latest('id')->first();
        $admin = $this->user('vwajen');
        $this->actingAs($admin)->get("/admin/reports/{$report->id}")->assertOk();
        $this->actingAs($admin)->post("/admin/reports/{$report->id}", ['decision' => 'suspension', 'reason' => 'Spam répété', 'days' => 3, 'apply_all' => 1])->assertRedirect();
        $this->assertSame('resolved', $report->fresh()->status);
        $this->assertSame('suspended', $post->user->fresh()->status);

        // Utilisateur suspendu : redirigé vers la page de restriction, peut faire appel
        $suspended = $post->user->fresh();
        $this->actingAs($suspended)->get('/')->assertRedirect(route('account.restricted'));
        $sanction = $suspended->sanctions()->latest()->first();
        $this->actingAs($suspended)->post("/appeals/{$sanction->id}", ['body' => 'Je conteste cette décision, ce n\'était pas du spam.'])->assertRedirect();
        $appeal = $sanction->appeals()->first();
        $this->actingAs($this->user('moderatris'))->post("/admin/appeals/{$appeal->id}", ['decision' => 'accepted', 'response' => 'Erreur de modération, désolé.'])->assertRedirect();
        $this->assertSame('active', $suspended->fresh()->status);

        // Masquer / restaurer un contenu
        $this->actingAs($admin)->post("/admin/content/posts/{$post->id}/hide", ['reason' => 'test'])->assertRedirect();
        $this->assertTrue($post->fresh()->is_hidden);
        $this->actingAs($admin)->post("/admin/content/posts/{$post->id}/restore")->assertRedirect();
        $this->assertFalse($post->fresh()->is_hidden);
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.hide']);

        // Supprimer avec le champ « Motif » laissé vide (arrive à null) : motif par défaut, pas d'erreur 500
        $live = Live::firstOrFail();
        $this->actingAs($admin)->post("/admin/content/lives/{$live->id}/delete", ['reason' => ''])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.remove']);
    }

    public function test_messaging_direct_and_group(): void
    {
        $a = $this->user('jeanmarc');
        $b = $this->user('nadege_l');
        $c = $this->user('kervens');
        $this->actingAs($a)->get("/messages/new?user={$b->username}")->assertRedirect();
        $conv = Conversation::latest('id')->first();
        $this->postJson("/messages/{$conv->id}", ['body' => 'Bonjou!'])->assertOk()->assertJsonStructure(['html', 'id']);
        $this->actingAs($b)->getJson("/messages/{$conv->id}/poll?after=0")->assertOk();
        $this->assertSame(0, $b->fresh()->unreadMessagesCount());

        $this->actingAs($a)->post('/messages', ['usernames' => [$b->username, $c->username], 'name' => 'Gwoup'])->assertRedirect();
        $group = Conversation::latest('id')->first();
        $this->assertSame('group', $group->type);
        $this->assertSame(3, $group->participants()->count());
    }

    public function test_events_communities_and_rsvp(): void
    {
        $me = $this->user('jeanmarc');
        $this->actingAs($me)->post('/events', [
            'title' => 'Reyinyon', 'start_date' => now()->addWeek()->toDateString(), 'start_time' => '15:00', 'timezone' => 'America/Port-au-Prince',
            'country' => 'HT', 'city' => 'Jacmel', 'lat' => 18.23, 'lng' => -72.53,
        ])->assertRedirect();
        $event = Event::latest('id')->first();
        $this->actingAs($this->user('nadege_l'))->postJson("/events/{$event->id}/rsvp", ['status' => 'going'])->assertOk();
        $this->assertSame(2, $event->fresh()->going_count);
        $this->get("/events/{$event->id}/ics")->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=utf-8');

        $this->actingAs($me)->post('/communities', ['name' => 'Kominote Tès', 'visibility' => 'private'])->assertRedirect();
        $community = Community::latest('id')->first();
        $this->actingAs($this->user('kervens'))->post("/communities/{$community->slug}/join")->assertRedirect();
        $member = $community->memberships()->where('status', 'pending')->first();
        $this->actingAs($me)->post("/communities/{$community->slug}/members/{$member->id}", ['action' => 'approve'])->assertRedirect();
        $this->assertSame(2, $community->fresh()->members_count);
    }

    public function test_program_versions_and_compare(): void
    {
        $candidate = $this->user('ndesrosiers');
        $this->actingAs($candidate)->post('/programs', ['title' => 'Pwogram tès', 'summary' => 'Rezime'])->assertRedirect();
        $program = Program::latest('id')->first();
        $this->post("/programs/{$program->id}/proposals", ['category_id' => 1, 'title' => 'Lopital', 'description' => 'Yon lopital pa komin',
            'source_name' => 'Etid', 'source_url' => 'https://example.org'])->assertRedirect();
        $this->post("/programs/{$program->id}/publish")->assertRedirect();
        $this->assertSame('published', $program->fresh()->status);

        $this->post("/programs/{$program->id}/versions")->assertRedirect();
        $this->assertSame(2, $program->versions()->count());
        $draft = $program->versions()->whereNull('published_at')->first();
        $this->assertSame(1, $draft->proposals()->count());
        $this->post("/programs/{$program->id}/publish")->assertRedirect();
        $this->assertSame($draft->id, $program->fresh()->current_version_id);
        $this->assertTrue($program->changeLogs()->exists());

        $this->get('/compare?c[]=ndesrosiers&c[]=mariange_t')->assertOk()->assertSee('Lopital');
    }

    public function test_verification_request_and_approval(): void
    {
        Storage::fake('local');
        $me = $this->user('jeanmarc');
        $this->actingAs($me)->post('/verification', ['type' => 'public', 'message' => 'Jounalis', 'documents' => [UploadedFile::fake()->create('id.pdf', 100, 'application/pdf')]])->assertRedirect();
        $vr = VerificationRequest::latest('id')->first();
        $this->actingAs($this->user('verifikate'))->post("/admin/verifications/{$vr->id}/approve", ['months' => 12])->assertRedirect();
        $me->refresh();
        $this->assertTrue($me->is_verified);
        $this->assertTrue($me->canGoLive());
    }

    public function test_live_webrtc_signaling_and_chat(): void
    {
        $host = $this->user('mariange_t');
        $viewer = $this->user('jeanmarc');
        // Non certifié : refusé
        $this->actingAs($viewer)->get('/lives/create')->assertForbidden();

        $this->actingAs($host)->post('/lives', ['title' => 'Live tès', 'kind' => 'video', 'chat_enabled' => 1])->assertRedirect();
        $live = Live::latest('id')->first();
        $this->postJson("/lives/{$live->id}/start")->assertJson(['status' => 'live']);
        $this->postJson("/lives/{$live->id}/heartbeat", ['peer_id' => 'hostpeer1', 'publish' => 1])->assertOk();

        $this->actingAs($viewer)->postJson("/lives/{$live->id}/heartbeat", ['peer_id' => 'viewerpeer1'])->assertOk()->assertJsonPath('hosts.0.peer_id', 'hostpeer1');
        $this->postJson("/lives/{$live->id}/signals", ['from' => 'viewerpeer1', 'to' => 'hostpeer1', 'type' => 'offer', 'payload' => '{"sdp":"x"}'])->assertOk();
        $this->postJson("/lives/{$live->id}/signals", ['from' => 'spoofed', 'to' => 'hostpeer1', 'type' => 'offer', 'payload' => '{}'])->assertForbidden();
        $this->postJson("/chat/live/{$live->id}", ['body' => 'Bonjou tout moun'])->assertOk();
        $this->postJson("/lives/{$live->id}/reactions", ['emoji' => '🔥'])->assertOk();

        $this->actingAs($host)->postJson("/lives/{$live->id}/heartbeat", ['peer_id' => 'hostpeer1', 'publish' => 1])->assertOk();
        $this->getJson("/lives/{$live->id}/signals?peer_id=hostpeer1&after=0")->assertOk()->assertJsonCount(1);
        $this->getJson("/chat/live/{$live->id}?after=0")->assertOk()->assertJsonCount(1, 'messages');

        $viewerRow = $live->viewers()->where('peer_id', 'viewerpeer1')->first();
        $this->postJson("/lives/{$live->id}/viewers/{$viewerRow->id}/ban")->assertOk();
        $this->actingAs($viewer)->postJson("/lives/{$live->id}/heartbeat", ['peer_id' => 'viewerpeer1'])->assertJson(['kicked' => true]);
        $this->actingAs($host)->postJson("/lives/{$live->id}/end")->assertJson(['status' => 'ended']);
    }

    public function test_settings_export_and_account_deletion(): void
    {
        $me = $this->user('jeanmarc');
        $this->actingAs($me);
        $this->put('/settings/privacy', ['is_private' => 1, 'allow_messages' => 'following', 'allow_comments' => 'everyone', 'allow_mentions' => 'everyone'])->assertRedirect();
        $this->assertTrue($me->fresh()->is_private);
        $this->put('/settings/accessibility', ['theme' => 'dark', 'font_size' => 'lg', 'high_contrast' => 1, 'data_saver' => 1])->assertRedirect();
        $this->assertTrue($me->fresh()->data_saver);
        $this->put('/settings/language', ['locale' => 'en'])->assertRedirect();
        $this->put('/settings/notifications', ['prefs' => ['like' => ['database' => 1, 'mail' => 1]]])->assertRedirect();
        $this->assertTrue($me->fresh()->wantsNotification('like', 'mail'));
        $this->assertFalse($me->fresh()->wantsNotification('comment', 'database'));

        $this->post('/settings/export')->assertOk()->assertDownload();

        $this->delete('/settings/account', ['password' => 'Vwajen2026!', 'confirmation' => 'SUPPRIMER'])->assertRedirect('/');
        $this->assertNotNull($me->fresh()->deletion_requested_at);
        $this->assertGuest();
        $this->get('/@jeanmarc')->assertNotFound();
    }

    public function test_translations_exist_for_locales(): void
    {
        foreach (['ht', 'en'] as $locale) {
            $this->assertFileExists(lang_path("$locale.json"));
        }
        $this->withCookie('locale', 'en')->get('/')->assertOk()->assertSee('lang="en"', false);
    }
}
