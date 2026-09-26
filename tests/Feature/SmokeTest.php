<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ContentController;
use App\Models\Community;
use App\Models\Debate;
use App\Models\Election;
use App\Models\Event;
use App\Models\Hashtag;
use App\Models\Live;
use App\Models\Post;
use App\Models\Program;
use App\Models\Question;
use App\Models\Report;
use App\Models\Source;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Parcourt les pages principales (invité, citoyen, candidat, administrateur) et vérifie qu'aucune ne plante. */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function check(array $urls, ?User $as = null, array $okCodes = [200, 302]): array
    {
        $failures = [];
        foreach ($urls as $url) {
            if ($as) {
                $this->actingAs($as);
            }
            $response = $this->get($url);
            if (! in_array($response->getStatusCode(), $okCodes, true)) {
                $msg = $response->exception ? get_class($response->exception).': '.$response->exception->getMessage().' @ '.basename($response->exception->getFile()).':'.$response->exception->getLine() : '';
                $failures[] = "[{$response->getStatusCode()}] {$url} {$msg}";
            }
        }

        return $failures;
    }

    public function test_pages_render(): void
    {
        $admin = User::where('username', 'vwajen')->first();
        $citizen = User::where('username', 'jeanmarc')->first();
        $candidate = User::where('username', 'mariange_t')->first();
        $official = User::where('username', 'depite_demo')->first();
        $post = Post::first();
        $program = Program::first();
        $question = Question::first();
        $debate = Debate::first();
        $live = Live::first();
        $event = Event::first();
        $community = Community::first();
        $discussion = $community->discussions()->first();
        $source = Source::first();
        $tag = Hashtag::first();
        $election = Election::first();

        $public = [
            '/', '/?feed=recent', '/?feed=popular', '/login', '/register', '/forgot-password',
            '/@'.$citizen->username, '/@'.$citizen->username.'?tab=media', '/@'.$citizen->username.'/followers', '/@'.$citizen->username.'/following',
            '/v/'.$post->id, '/hashtags/'.$tag->name, '/candidates', '/candidates/'.$candidate->username,
            '/candidates/'.$candidate->username.'?tab=program', '/candidates/'.$candidate->username.'?tab=sources', '/candidates/'.$candidate->username.'?tab=history',
            '/candidates/'.$candidate->username.'?tab=questions', '/programs/'.$program->id, '/sources/'.$source->id, '/compare',
            '/compare?c[]='.$candidate->username.'&c[]=jebelizaire', '/questions', '/questions?status=answered', '/questions/'.$question->id,
            '/debates', '/debates/'.$debate->id, '/lives', '/spaces', '/lives/'.$live->id, '/shorts', '/videos', '/discover', '/discover?section=users',
            '/trends', '/search', '/search?q=ayiti', '/search?q=sante&type=posts', '/search?q=marie&type=candidates', '/events', '/events?when=past',
            '/events/'.$event->id, '/events/'.$event->id.'/ics', '/communities', '/communities/'.$community->slug, '/communities/'.$community->slug.'?tab=discussions',
            '/communities/'.$community->slug.'/members', '/communities/'.$community->slug.'/discussions/'.$discussion->id, '/map', '/map/data?types[]=events&types[]=lives&types[]=candidates',
            '/mond', '/mond/US', '/officials', '/officials/'.$official->username, '/officials/'.$official->username.'?tab=commitments',
            '/podcasts', '/elections', '/elections/'.$election->id, '/observatory', '/civic-data', '/ambassadors', '/developers',
            '/pages/privacy', '/pages/terms', '/pages/cookies', '/pages/about', '/pages/rules', '/pages/accessibility', '/pages/sensitive-data', '/offline',
            '/manifest.webmanifest', '/embed/post/'.$post->id, '/embed/live/'.$live->id, '/api/v1/candidates', '/api/v1/stats', '/api/v1/candidates/'.$candidate->username.'/program',
        ];
        $failures = $this->check($public);

        $member = [
            '/?feed=personalized', '/?feed=chronological', '/notifications', '/messages', '/messages/new', '/messages/search?q=a', '/follow-requests',
            '/settings/profile', '/settings/account', '/settings/privacy', '/settings/notifications', '/settings/accessibility', '/settings/language',
            '/settings/interests', '/settings/sessions', '/settings/devices', '/settings/blocked', '/settings/sanctions', '/settings/data', '/settings/cookies',
            '/settings/api-keys', '/verification', '/questions/create', '/questions/create?to='.$candidate->username, '/my/questions', '/events/create',
            '/communities/create', '/videos/create', '/videos/create?kind=short', '/podcasts/create', '/report/post/'.$post->id, '/report/user/'.$candidate->id,
            '/candidates/register', '/phone/verify', '/@'.$citizen->username.'?tab=saved', '/messages/new?user='.$candidate->username,
        ];
        $failures = array_merge($failures, $this->check($member, $citizen));

        $cand = [
            '/candidate/dashboard', '/candidate/dashboard/posts', '/candidate/dashboard/questions', '/candidate/dashboard/program', '/candidate/dashboard/profile',
            '/candidate/dashboard/stats', '/candidate/dashboard/lives', '/candidate/dashboard/events', '/candidate/dashboard/debates', '/candidate/dashboard/notifications',
            '/candidate/dashboard/videos', '/candidate/dashboard/shorts', '/candidate/dashboard/analytics', '/candidate/analytics/export', '/programs/create', '/programs/'.$program->id.'/edit', '/lives/create', '/debates/create',
            '/lives/'.$live->id,
        ];
        $failures = array_merge($failures, $this->check($cand, $candidate));

        $report = Report::create(['reporter_id' => $citizen->id, 'reportable_type' => 'post', 'reportable_id' => $post->id, 'reported_user_id' => $post->user_id, 'reason' => 'spam']);
        $vr = VerificationRequest::create(['user_id' => $citizen->id, 'type' => 'public', 'message' => 'test']);
        $adminUrls = [
            '/admin', '/admin/monitoring', '/admin/stats', '/admin/users', '/admin/users/'.$citizen->id, '/admin/candidates', '/admin/organizations', '/admin/officials',
            '/admin/verifications', '/admin/verifications/'.$vr->id, '/admin/sources', '/admin/reports', '/admin/reports/'.$report->id, '/admin/sanctions', '/admin/appeals',
            '/admin/audit', '/admin/settings', '/admin/categories', '/admin/languages', '/admin/languages/1/translations', '/admin/announcements', '/admin/roles',
            '/admin/elections', '/admin/newsletter', '/admin/ambassadors', '/admin/hashtags', '/debates/'.$debate->id.'/edit',
        ];
        foreach (ContentController::TYPES as $type => $m) {
            $adminUrls[] = '/admin/content/'.$type;
        }
        $failures = array_merge($failures, $this->check($adminUrls, $admin));

        $this->assertSame([], $failures, "Pages en erreur :\n".implode("\n", $failures));
    }
}
