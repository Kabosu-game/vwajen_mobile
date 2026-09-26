<?php

use App\Http\Controllers\Admin as A;
use App\Http\Controllers as C;
use App\Http\Controllers\Auth as Au;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Pages publiques
|--------------------------------------------------------------------------
*/
Route::get('/', [C\HomeController::class, 'index'])->name('home');
Route::get('/locale/{locale}', [C\PageController::class, 'locale'])->name('locale.switch');
Route::post('/cookies/consent', [C\PageController::class, 'cookieConsent'])->name('cookies.consent');
Route::get('/pages/{page}', [C\PageController::class, 'show'])->name('pages.show')
    ->whereIn('page', ['privacy', 'terms', 'cookies', 'about', 'rules', 'accessibility', 'sensitive-data']);
Route::get('/offline', [C\PageController::class, 'offline'])->name('offline');
Route::get('/manifest.webmanifest', [C\PageController::class, 'manifest'])->name('manifest');
Route::get('/developers', [C\PageController::class, 'developers'])->name('developers');
Route::get('/embed/video/{video}', [C\EmbedController::class, 'video'])->name('embed.video');
Route::get('/embed/live/{live}', [C\EmbedController::class, 'live'])->name('embed.live');
Route::get('/embed/post/{post}', [C\EmbedController::class, 'post'])->name('embed.post');

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [Au\RegisterController::class, 'create'])->name('register');
    Route::post('/register', [Au\RegisterController::class, 'store'])->middleware('throttle:register');
    Route::get('/login', [Au\LoginController::class, 'create'])->name('login');
    Route::post('/login', [Au\LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/forgot-password', [Au\PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [Au\PasswordController::class, 'email'])->middleware('throttle:password')->name('password.email');
    Route::get('/reset-password/{token}', [Au\PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [Au\PasswordController::class, 'update'])->middleware('throttle:password')->name('password.update');
});
Route::get('/auth/{provider}/redirect', [Au\SocialController::class, 'redirect'])->whereIn('provider', ['google', 'apple'])->name('social.redirect');
Route::match(['get', 'post'], '/auth/{provider}/callback', [Au\SocialController::class, 'callback'])->whereIn('provider', ['google', 'apple'])
    ->withoutMiddleware([ValidateCsrfToken::class])->name('social.callback');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [Au\LoginController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [Au\VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [Au\VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [Au\VerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/phone/verify', [Au\PhoneController::class, 'show'])->name('phone.verify');
    Route::post('/phone/send', [Au\PhoneController::class, 'send'])->middleware('throttle:sms')->name('phone.send');
    Route::post('/phone/verify', [Au\PhoneController::class, 'confirm'])->middleware('throttle:10,1')->name('phone.confirm');
    Route::get('/account/restricted', [C\AppealController::class, 'restricted'])->name('account.restricted');
    Route::get('/appeals/{sanction}/create', [C\AppealController::class, 'create'])->name('appeals.create');
    Route::post('/appeals/{sanction}', [C\AppealController::class, 'store'])->name('appeals.store');
});

/*
|--------------------------------------------------------------------------
| Contenus consultables sans compte
|--------------------------------------------------------------------------
*/
Route::get('/@{user}', [C\ProfileController::class, 'show'])->name('profile.show');
Route::get('/@{user}/followers', [C\ProfileController::class, 'followers'])->name('profile.followers');
Route::get('/@{user}/following', [C\ProfileController::class, 'following'])->name('profile.following');
Route::get('/v/{post}', [C\PostController::class, 'show'])->name('posts.show');
Route::get('/hashtags/{hashtag}', [C\HashtagController::class, 'show'])->name('hashtags.show');
Route::get('/i/{type}/{id}/comments', [C\CommentController::class, 'index'])->name('comments.index');
Route::get('/i/{type}/{id}/likers', [C\InteractionController::class, 'likers'])->name('interact.likers');

Route::get('/candidates', [C\CandidateController::class, 'index'])->name('candidates.index');
Route::get('/candidates/{user}', [C\CandidateController::class, 'show'])->name('candidates.show')->where('user', '^(?!register$)[A-Za-z0-9_]+');
Route::get('/programs/{program}', [C\ProgramController::class, 'show'])->name('programs.show')->whereNumber('program');
Route::get('/programs/{program}/versions/{version}', [C\ProgramController::class, 'version'])->name('programs.version');
Route::get('/sources/{source}', [C\SourceController::class, 'show'])->name('sources.show');
Route::get('/compare', [C\CompareController::class, 'index'])->name('compare.index');

Route::get('/questions', [C\QuestionController::class, 'index'])->name('questions.index');
Route::get('/questions/{question}', [C\QuestionController::class, 'show'])->name('questions.show')->whereNumber('question');
Route::get('/debates', [C\DebateController::class, 'index'])->name('debates.index');
Route::get('/debates/{debate}', [C\DebateController::class, 'show'])->name('debates.show')->whereNumber('debate');
Route::get('/lives', [C\LiveController::class, 'index'])->name('lives.index');
Route::get('/spaces', [C\LiveController::class, 'spaces'])->name('spaces.index');
Route::get('/lives/{live}', [C\LiveController::class, 'show'])->name('lives.show')->whereNumber('live');
Route::get('/chat/{type}/{id}', [C\ChatController::class, 'index'])->name('chat.index')->whereIn('type', ['live', 'debate']);
Route::get('/shorts', [C\ShortController::class, 'index'])->name('shorts.index');
Route::get('/shorts/feed', [C\ShortController::class, 'feed'])->name('shorts.feed');
Route::get('/shorts/{video}', [C\ShortController::class, 'show'])->name('shorts.show')->whereNumber('video');
Route::get('/videos', [C\VideoController::class, 'index'])->name('videos.index');
Route::get('/videos/{video}', [C\VideoController::class, 'show'])->name('videos.show')->whereNumber('video');
Route::post('/videos/{video}/view', [C\VideoController::class, 'view'])->middleware('throttle:interact')->name('videos.view');
Route::get('/discover', [C\DiscoverController::class, 'index'])->name('discover.index');
Route::get('/trends', [C\DiscoverController::class, 'trends'])->name('trends');
Route::get('/search', [C\SearchController::class, 'index'])->middleware('throttle:search')->name('search.index');
Route::get('/search/suggest', [C\SearchController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');
Route::get('/events', [C\EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [C\EventController::class, 'show'])->name('events.show')->whereNumber('event');
Route::get('/events/{event}/ics', [C\EventController::class, 'ics'])->name('events.ics');
Route::get('/communities', [C\CommunityController::class, 'index'])->name('communities.index');
Route::get('/communities/{community}', [C\CommunityController::class, 'show'])->name('communities.show')->where('community', '^(?!create$)[A-Za-z0-9\-]+');
Route::get('/communities/{community}/members', [C\CommunityController::class, 'members'])->name('communities.members');
Route::get('/communities/{community}/discussions/{discussion}', [C\DiscussionController::class, 'show'])->name('discussions.show');
Route::get('/map', [C\MapController::class, 'index'])->name('map.index');
Route::get('/map/data', [C\MapController::class, 'data'])->name('map.data');
Route::get('/mond', [C\MondController::class, 'index'])->name('mond.index');
Route::get('/mond/{country}', [C\MondController::class, 'country'])->name('mond.country')->where('country', '[A-Z]{2}');
Route::get('/officials', [C\OfficialController::class, 'index'])->name('officials.index');
Route::get('/officials/{user}', [C\OfficialController::class, 'show'])->name('officials.show');
Route::get('/podcasts', [C\PodcastController::class, 'index'])->name('podcasts.index');
Route::get('/podcasts/{podcast}', [C\PodcastController::class, 'show'])->name('podcasts.show')->whereNumber('podcast');
Route::post('/podcast-episodes/{episode}/play', [C\PodcastController::class, 'play'])->middleware('throttle:interact')->name('podcasts.play');
Route::get('/elections', [C\ElectionController::class, 'index'])->name('elections.index');
Route::get('/elections/{election}', [C\ElectionController::class, 'show'])->name('elections.show');
Route::get('/observatory', [C\ObservatoryController::class, 'index'])->name('observatory.index');
Route::get('/civic-data', [C\ObservatoryController::class, 'civicData'])->name('civic.index');
Route::post('/newsletter', [C\NewsletterController::class, 'subscribe'])->middleware('throttle:5,1')->name('newsletter.subscribe');
Route::get('/newsletter/confirm/{token}', [C\NewsletterController::class, 'confirm'])->name('newsletter.confirm');
Route::get('/newsletter/unsubscribe/{token}', [C\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::get('/ambassadors', [C\AmbassadorController::class, 'index'])->name('ambassadors.index');
Route::post('/i/{type}/{id}/share', [C\InteractionController::class, 'share'])->middleware('throttle:interact')->name('interact.share');

/*
|--------------------------------------------------------------------------
| Espace connecté
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Vwa (publications)
    Route::post('/posts', [C\PostController::class, 'store'])->middleware('throttle:publish')->name('posts.store');
    Route::get('/v/{post}/edit', [C\PostController::class, 'edit'])->name('posts.edit');
    Route::put('/v/{post}', [C\PostController::class, 'update'])->name('posts.update');
    Route::delete('/v/{post}', [C\PostController::class, 'destroy'])->name('posts.destroy');
    Route::get('/link-preview', [C\PostController::class, 'preview'])->middleware('throttle:30,1')->name('posts.preview');
    Route::post('/polls/{poll}/vote', [C\PostController::class, 'vote'])->middleware('throttle:interact')->name('polls.vote');

    // Interactions génériques
    Route::middleware('throttle:interact')->group(function () {
        Route::post('/i/{type}/{id}/like', [C\InteractionController::class, 'like'])->name('interact.like');
        Route::post('/i/{type}/{id}/bookmark', [C\InteractionController::class, 'bookmark'])->name('interact.bookmark');
        Route::post('/i/{type}/{id}/repost', [C\InteractionController::class, 'repost'])->name('interact.repost');
        Route::post('/i/{type}/{id}/hide', [C\InteractionController::class, 'hide'])->name('interact.hide');
        Route::post('/i/{type}/{id}/translate', [C\InteractionController::class, 'translate'])->middleware('throttle:20,1')->name('interact.translate');
        Route::post('/i/{type}/{id}/send', [C\InteractionController::class, 'sendInternal'])->name('interact.send');
        Route::post('/comments/{comment}/like', [C\CommentController::class, 'like'])->name('comments.like');
    });
    Route::post('/i/{type}/{id}/comments', [C\CommentController::class, 'store'])->middleware('throttle:comment')->name('comments.store');
    Route::put('/comments/{comment}', [C\CommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [C\CommentController::class, 'destroy'])->name('comments.destroy');

    // Signalements
    Route::get('/report/{type}/{id}', [C\ReportController::class, 'create'])->name('reports.create');
    Route::post('/report/{type}/{id}', [C\ReportController::class, 'store'])->middleware('throttle:report')->name('reports.store');

    // Abonnements, blocages
    Route::middleware('throttle:follow')->group(function () {
        Route::post('/users/{user}/follow', [C\FollowController::class, 'follow'])->name('users.follow');
        Route::delete('/users/{user}/follow', [C\FollowController::class, 'unfollow'])->name('users.unfollow');
        Route::post('/users/{user}/notify', [C\FollowController::class, 'toggleNotify'])->name('users.notify');
    });
    Route::get('/follow-requests', [C\FollowController::class, 'requests'])->name('follow.requests');
    Route::post('/follow-requests/{follow}/accept', [C\FollowController::class, 'accept'])->name('follow.accept');
    Route::delete('/follow-requests/{follow}', [C\FollowController::class, 'decline'])->name('follow.decline');
    Route::post('/users/{user}/block', [C\BlockController::class, 'block'])->name('users.block');
    Route::delete('/users/{user}/block', [C\BlockController::class, 'unblock'])->name('users.unblock');
    Route::post('/users/{user}/mute', [C\BlockController::class, 'toggleMute'])->name('users.mute');
    Route::post('/hashtags/{hashtag}/follow', [C\HashtagController::class, 'follow'])->name('hashtags.follow');

    // Candidats
    Route::get('/candidates/register', [C\CandidateController::class, 'register'])->name('candidates.register');
    Route::post('/candidates/register', [C\CandidateController::class, 'storeRegistration'])->name('candidates.register.store');
    Route::prefix('candidate')->name('candidate.')->group(function () {
        Route::get('/dashboard/{section?}', [C\CandidateDashboardController::class, 'index'])->name('dashboard')
            ->whereIn('section', ['overview', 'posts', 'videos', 'shorts', 'lives', 'questions', 'program', 'events', 'debates', 'notifications', 'profile', 'stats', 'analytics']);
        Route::put('/profile', [C\CandidateDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('/analytics/export', [C\CandidateDashboardController::class, 'exportAnalytics'])->name('analytics.export');
    });

    // Programmes & propositions
    Route::get('/programs/create', [C\ProgramController::class, 'create'])->name('programs.create');
    Route::post('/programs', [C\ProgramController::class, 'store'])->name('programs.store');
    Route::get('/programs/{program}/edit', [C\ProgramController::class, 'edit'])->name('programs.edit');
    Route::put('/programs/{program}', [C\ProgramController::class, 'update'])->name('programs.update');
    Route::post('/programs/{program}/publish', [C\ProgramController::class, 'publish'])->name('programs.publish');
    Route::post('/programs/{program}/archive', [C\ProgramController::class, 'archive'])->name('programs.archive');
    Route::post('/programs/{program}/unarchive', [C\ProgramController::class, 'unarchive'])->name('programs.unarchive');
    Route::post('/programs/{program}/versions', [C\ProgramController::class, 'newVersion'])->name('programs.versions.store');
    Route::delete('/programs/{program}', [C\ProgramController::class, 'destroy'])->name('programs.destroy');
    Route::post('/programs/{program}/proposals', [C\ProposalController::class, 'store'])->name('proposals.store');
    Route::put('/proposals/{proposal}', [C\ProposalController::class, 'update'])->name('proposals.update');
    Route::delete('/proposals/{proposal}', [C\ProposalController::class, 'destroy'])->name('proposals.destroy');
    Route::post('/programs/{program}/documents', [C\ProgramController::class, 'addDocument'])->name('programs.documents.store');
    Route::delete('/documents/{document}', [C\ProgramController::class, 'removeDocument'])->name('documents.destroy');
    Route::post('/sources/{type}/{id}', [C\SourceController::class, 'store'])->name('sources.store');
    Route::delete('/sources/{source}', [C\SourceController::class, 'destroy'])->name('sources.destroy');

    // Kesyon pou kandida yo
    Route::get('/questions/create', [C\QuestionController::class, 'create'])->name('questions.create');
    Route::post('/questions', [C\QuestionController::class, 'store'])->middleware('throttle:publish')->name('questions.store');
    Route::get('/my/questions', [C\QuestionController::class, 'mine'])->name('questions.mine');
    Route::post('/questions/{question}/support', [C\QuestionController::class, 'support'])->middleware('throttle:interact')->name('questions.support');
    Route::post('/questions/{question}/answers', [C\QuestionController::class, 'answer'])->name('questions.answer');
    Route::post('/questions/{question}/close', [C\QuestionController::class, 'close'])->name('questions.close');
    Route::post('/questions/{question}/reopen', [C\QuestionController::class, 'reopen'])->name('questions.reopen');
    Route::delete('/questions/{question}', [C\QuestionController::class, 'destroy'])->name('questions.destroy');

    // Débats
    Route::get('/debates/create', [C\DebateController::class, 'create'])->name('debates.create');
    Route::post('/debates', [C\DebateController::class, 'store'])->name('debates.store');
    Route::get('/debates/{debate}/edit', [C\DebateController::class, 'edit'])->name('debates.edit');
    Route::put('/debates/{debate}', [C\DebateController::class, 'update'])->name('debates.update');
    Route::delete('/debates/{debate}', [C\DebateController::class, 'destroy'])->name('debates.destroy');
    Route::post('/debates/{debate}/participants', [C\DebateController::class, 'invite'])->name('debates.invite');
    Route::delete('/debates/{debate}/participants/{participant}', [C\DebateController::class, 'removeParticipant'])->name('debates.participants.remove');
    Route::post('/debates/{debate}/respond', [C\DebateController::class, 'respond'])->name('debates.respond');
    Route::post('/debates/{debate}/start', [C\DebateController::class, 'start'])->name('debates.start');
    Route::post('/debates/{debate}/end', [C\DebateController::class, 'end'])->name('debates.end');
    Route::post('/debates/{debate}/archive', [C\DebateController::class, 'archive'])->name('debates.archive');
    Route::post('/debates/{debate}/questions', [C\DebateController::class, 'askQuestion'])->middleware('throttle:comment')->name('debates.questions.store');
    Route::post('/debate-questions/{question}/vote', [C\DebateController::class, 'voteQuestion'])->name('debates.questions.vote');
    Route::post('/debate-questions/{question}/status', [C\DebateController::class, 'questionStatus'])->name('debates.questions.status');
    Route::post('/debates/{debate}/remind', [C\DebateController::class, 'remind'])->name('debates.remind');
    Route::post('/debates/{debate}/summary', [C\DebateController::class, 'summary'])->middleware('throttle:5,1')->name('debates.summary');

    // Vwajèn Live
    Route::get('/lives/create', [C\LiveController::class, 'create'])->name('lives.create');
    Route::post('/lives', [C\LiveController::class, 'store'])->name('lives.store');
    Route::get('/lives/{live}/edit', [C\LiveController::class, 'edit'])->name('lives.edit');
    Route::put('/lives/{live}', [C\LiveController::class, 'update'])->name('lives.update');
    Route::delete('/lives/{live}', [C\LiveController::class, 'destroy'])->name('lives.destroy');
    Route::post('/lives/{live}/start', [C\LiveController::class, 'start'])->name('lives.start');
    Route::post('/lives/{live}/end', [C\LiveController::class, 'end'])->name('lives.end');
    Route::post('/lives/{live}/cancel', [C\LiveController::class, 'cancel'])->name('lives.cancel');
    Route::post('/lives/{live}/invite', [C\LiveController::class, 'invite'])->name('lives.invite');
    Route::post('/lives/{live}/respond', [C\LiveController::class, 'respond'])->name('lives.respond');
    Route::post('/lives/{live}/viewers/{viewer}/kick', [C\LiveController::class, 'kick'])->name('lives.kick');
    Route::post('/lives/{live}/viewers/{viewer}/ban', [C\LiveController::class, 'ban'])->name('lives.ban');
    Route::post('/lives/{live}/reactions', [C\LiveController::class, 'react'])->middleware('throttle:60,1')->name('lives.react');
    Route::post('/lives/{live}/remind', [C\LiveController::class, 'remind'])->name('lives.remind');
    Route::post('/lives/{live}/replay', [C\LiveController::class, 'replay'])->name('lives.replay');
    Route::post('/lives/{live}/settings', [C\LiveController::class, 'chatSettings'])->name('lives.chat-settings');

    // Chat en direct
    Route::post('/chat/{type}/{id}', [C\ChatController::class, 'store'])->middleware('throttle:chat')->name('chat.store')->whereIn('type', ['live', 'debate']);
    Route::post('/chat/messages/{message}/hide', [C\ChatController::class, 'hide'])->name('chat.hide');
    Route::post('/chat/messages/{message}/pin', [C\ChatController::class, 'pin'])->name('chat.pin');

    // Vidéos & Shorts
    Route::get('/videos/create', [C\VideoController::class, 'create'])->name('videos.create');
    Route::post('/videos', [C\VideoController::class, 'store'])->middleware('throttle:publish')->name('videos.store');
    Route::get('/videos/{video}/edit', [C\VideoController::class, 'edit'])->name('videos.edit');
    Route::put('/videos/{video}', [C\VideoController::class, 'update'])->name('videos.update');
    Route::delete('/videos/{video}', [C\VideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/{video}/subtitles', [C\VideoController::class, 'addSubtitle'])->name('videos.subtitles.store');
    Route::post('/videos/{video}/auto-subtitles', [C\VideoController::class, 'autoSubtitles'])->middleware('throttle:5,1')->name('videos.subtitles.auto');
    Route::delete('/subtitles/{subtitle}', [C\VideoController::class, 'removeSubtitle'])->name('videos.subtitles.destroy');
    Route::get('/shorts/create', [C\ShortController::class, 'create'])->name('shorts.create');

    // Téléversements fragmentés (reprise des uploads interrompus)
    Route::middleware('throttle:upload')->group(function () {
        Route::post('/uploads', [C\UploadController::class, 'init'])->name('uploads.init');
        Route::get('/uploads/{uuid}', [C\UploadController::class, 'status'])->name('uploads.status');
        Route::post('/uploads/{uuid}/chunk', [C\UploadController::class, 'chunk'])->name('uploads.chunk');
    });

    // Événements
    Route::get('/events/create', [C\EventController::class, 'create'])->name('events.create');
    Route::post('/events', [C\EventController::class, 'store'])->middleware('throttle:publish')->name('events.store');
    Route::get('/events/{event}/edit', [C\EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [C\EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [C\EventController::class, 'destroy'])->name('events.destroy');
    Route::post('/events/{event}/rsvp', [C\EventController::class, 'rsvp'])->name('events.rsvp');
    Route::post('/events/{event}/remind', [C\EventController::class, 'remind'])->name('events.remind');

    // Notifications
    Route::get('/notifications', [C\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [C\NotificationController::class, 'count'])->name('notifications.count');
    Route::post('/notifications/read-all', [C\NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [C\NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('/notifications/{id}', [C\NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/push/subscribe', [C\NotificationController::class, 'subscribe'])->name('push.subscribe');
    Route::post('/push/unsubscribe', [C\NotificationController::class, 'unsubscribe'])->name('push.unsubscribe');

    // Messagerie
    Route::get('/messages', [C\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/new', [C\MessageController::class, 'create'])->name('messages.create');
    Route::get('/messages/search', [C\MessageController::class, 'search'])->name('messages.search');
    Route::post('/messages', [C\MessageController::class, 'store'])->middleware('throttle:message')->name('messages.store');
    Route::get('/messages/{conversation}', [C\MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}', [C\MessageController::class, 'send'])->middleware('throttle:message')->name('messages.send');
    Route::get('/messages/{conversation}/poll', [C\MessageController::class, 'poll'])->name('messages.poll');
    Route::delete('/messages/{conversation}', [C\MessageController::class, 'destroy'])->name('messages.destroy');
    Route::post('/messages/{conversation}/participants', [C\MessageController::class, 'addParticipants'])->name('messages.participants');
    Route::post('/messages/{conversation}/leave', [C\MessageController::class, 'leave'])->name('messages.leave');
    Route::post('/messages/{conversation}/rename', [C\MessageController::class, 'rename'])->name('messages.rename');
    Route::delete('/message/{message}', [C\MessageController::class, 'deleteMessage'])->name('messages.delete-message');

    // Communautés
    Route::get('/communities/create', [C\CommunityController::class, 'create'])->name('communities.create');
    Route::post('/communities', [C\CommunityController::class, 'store'])->name('communities.store');
    Route::get('/communities/{community}/edit', [C\CommunityController::class, 'edit'])->name('communities.edit');
    Route::put('/communities/{community}', [C\CommunityController::class, 'update'])->name('communities.update');
    Route::delete('/communities/{community}', [C\CommunityController::class, 'destroy'])->name('communities.destroy');
    Route::post('/communities/{community}/join', [C\CommunityController::class, 'join'])->name('communities.join');
    Route::post('/communities/{community}/leave', [C\CommunityController::class, 'leave'])->name('communities.leave');
    Route::post('/communities/{community}/members/{member}', [C\CommunityController::class, 'updateMember'])->name('communities.members.update');
    Route::post('/communities/{community}/discussions', [C\DiscussionController::class, 'store'])->middleware('throttle:publish')->name('discussions.store');
    Route::post('/communities/{community}/discussions/{discussion}/moderate', [C\DiscussionController::class, 'moderate'])->name('discussions.moderate');
    Route::delete('/communities/{community}/discussions/{discussion}', [C\DiscussionController::class, 'destroy'])->name('discussions.destroy');

    // Responsables publics
    Route::post('/officials/{user}/records', [C\OfficialController::class, 'storeRecord'])->name('officials.records.store');
    Route::delete('/records/{record}', [C\OfficialController::class, 'destroyRecord'])->name('officials.records.destroy');
    Route::post('/officials/{user}/commitments', [C\OfficialController::class, 'storeCommitment'])->name('officials.commitments.store');
    Route::post('/commitments/{commitment}/updates', [C\OfficialController::class, 'updateCommitment'])->name('officials.commitments.update');

    // Vérification
    Route::get('/verification', [C\VerificationController::class, 'index'])->name('verification.index');
    Route::post('/verification', [C\VerificationController::class, 'store'])->name('verification.store');
    Route::post('/verification/{verification}/cancel', [C\VerificationController::class, 'cancel'])->name('verification.cancel');

    // Podcasts, ambassadeurs
    Route::get('/podcasts/create', [C\PodcastController::class, 'create'])->name('podcasts.create');
    Route::post('/podcasts', [C\PodcastController::class, 'store'])->name('podcasts.store');
    Route::post('/podcasts/{podcast}/episodes', [C\PodcastController::class, 'storeEpisode'])->name('podcasts.episodes.store');
    Route::delete('/podcasts/{podcast}', [C\PodcastController::class, 'destroy'])->name('podcasts.destroy');
    Route::post('/ambassadors', [C\AmbassadorController::class, 'store'])->name('ambassadors.store');

    // Paramètres
    Route::prefix('settings')->name('settings.')->controller(C\SettingsController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
        Route::get('/account', 'account')->name('account');
        Route::put('/account', 'updateAccount')->name('account.update');
        Route::put('/password', 'updatePassword')->name('password.update');
        Route::get('/privacy', 'privacy')->name('privacy');
        Route::put('/privacy', 'updatePrivacy')->name('privacy.update');
        Route::get('/notifications', 'notifications')->name('notifications');
        Route::put('/notifications', 'updateNotifications')->name('notifications.update');
        Route::get('/accessibility', 'accessibility')->name('accessibility');
        Route::put('/accessibility', 'updateAccessibility')->name('accessibility.update');
        Route::post('/theme', 'toggleTheme')->name('theme');
        Route::get('/language', 'language')->name('language');
        Route::put('/language', 'updateLanguage')->name('language.update');
        Route::get('/interests', 'interests')->name('interests');
        Route::put('/interests', 'updateInterests')->name('interests.update');
        Route::get('/sessions', 'sessions')->name('sessions');
        Route::delete('/sessions/others', 'destroyOtherSessions')->name('sessions.destroy-others');
        Route::delete('/sessions/{id}', 'destroySession')->name('sessions.destroy');
        Route::get('/devices', 'devices')->name('devices');
        Route::put('/devices/{device}', 'updateDevice')->name('devices.update');
        Route::delete('/devices/{device}', 'revokeDevice')->name('devices.revoke');
        Route::get('/blocked', 'blocked')->name('blocked');
        Route::get('/sanctions', 'sanctions')->name('sanctions');
        Route::get('/data', 'data')->name('data');
        Route::post('/export', 'export')->middleware('throttle:3,60')->name('export');
        Route::delete('/account', 'destroyAccount')->name('account.destroy');
        Route::post('/account/cancel-deletion', 'cancelDeletion')->name('account.cancel-deletion');
        Route::get('/cookies', 'cookies')->name('cookies');
        Route::put('/cookies', 'updateCookies')->name('cookies.update');
        Route::get('/api-keys', 'apiKeys')->name('api-keys');
        Route::post('/api-keys', 'createApiKey')->name('api-keys.store');
        Route::delete('/api-keys/{key}', 'revokeApiKey')->name('api-keys.destroy');
    });
});

// Signalisation WebRTC des lives (spectateurs invités autorisés via identifiant de pair)
Route::middleware('throttle:signal')->group(function () {
    Route::post('/lives/{live}/heartbeat', [C\LiveController::class, 'heartbeat'])->name('lives.heartbeat');
    Route::get('/lives/{live}/signals', [C\LiveController::class, 'signals'])->name('lives.signals');
    Route::post('/lives/{live}/signals', [C\LiveController::class, 'signal'])->name('lives.signal');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'permission:staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [A\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/monitoring', [A\DashboardController::class, 'monitoring'])->middleware('permission:system.monitor')->name('monitoring');
    Route::get('/stats', [A\StatsController::class, 'index'])->middleware('permission:stats.view')->name('stats');
    Route::get('/stats/export', [A\StatsController::class, 'export'])->middleware('permission:stats.view')->name('stats.export');

    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [A\UserController::class, 'index'])->name('users.index');
        Route::get('/users/{id}', [A\UserController::class, 'show'])->name('users.show')->whereNumber('id');
    });
    Route::middleware('permission:users.manage')->group(function () {
        Route::put('/users/{id}', [A\UserController::class, 'update'])->name('users.update');
        Route::post('/users/{id}/roles', [A\UserController::class, 'roles'])->middleware('permission:roles.manage')->name('users.roles');
        Route::post('/users/{id}/logout', [A\UserController::class, 'logoutEverywhere'])->name('users.logout');
        Route::delete('/users/{id}', [A\UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{id}/restore', [A\UserController::class, 'restore'])->name('users.restore');
    });

    Route::middleware('permission:candidates.manage')->group(function () {
        Route::get('/candidates', [A\CandidateController::class, 'index'])->name('candidates.index');
        Route::put('/candidates/{profile}', [A\CandidateController::class, 'update'])->name('candidates.update');
        Route::get('/organizations', [A\CandidateController::class, 'organizations'])->name('organizations.index');
        Route::post('/organizations/{organization}/professional', [A\CandidateController::class, 'toggleProfessional'])->name('organizations.professional');
        Route::get('/officials', [A\CandidateController::class, 'officials'])->name('officials.index');
        Route::post('/officials', [A\CandidateController::class, 'storeOfficial'])->name('officials.store');
    });

    Route::middleware('permission:verifications.manage')->group(function () {
        Route::get('/verifications', [A\VerificationController::class, 'index'])->name('verifications.index');
        Route::get('/verifications/{verification}', [A\VerificationController::class, 'show'])->name('verifications.show');
        Route::post('/verifications/{verification}/approve', [A\VerificationController::class, 'approve'])->name('verifications.approve');
        Route::post('/verifications/{verification}/reject', [A\VerificationController::class, 'reject'])->name('verifications.reject');
        Route::get('/verification-documents/{document}', [A\VerificationController::class, 'document'])->name('verifications.document');
        Route::post('/users/{id}/unverify', [A\VerificationController::class, 'revoke'])->name('verifications.revoke');
        Route::get('/sources', [A\SourceController::class, 'index'])->name('sources.index');
        Route::put('/sources/{source}', [A\SourceController::class, 'update'])->name('sources.update');
    });

    Route::middleware('permission:moderation.manage')->group(function () {
        Route::get('/content/{type}', [A\ContentController::class, 'index'])->name('content.index');
        Route::post('/content/{type}/{id}/{action}', [A\ContentController::class, 'action'])->name('content.action')
            ->whereIn('action', ['hide', 'restore', 'delete', 'feature']);
        Route::get('/reports', [A\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [A\ReportController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}', [A\ReportController::class, 'resolve'])->name('reports.resolve');
        Route::post('/users/{id}/sanction', [A\SanctionController::class, 'store'])->name('sanctions.store');
        Route::get('/sanctions', [A\SanctionController::class, 'index'])->name('sanctions.index');
        Route::post('/sanctions/{sanction}/revoke', [A\SanctionController::class, 'revoke'])->name('sanctions.revoke');
        Route::get('/appeals', [A\SanctionController::class, 'appeals'])->name('appeals.index');
        Route::post('/appeals/{appeal}', [A\SanctionController::class, 'decideAppeal'])->name('appeals.decide');
        Route::get('/hashtags', [A\ContentController::class, 'hashtags'])->name('hashtags.index');
        Route::post('/hashtags/{hashtag}/toggle', [A\ContentController::class, 'toggleHashtag'])->name('hashtags.toggle');
    });

    Route::get('/audit', [A\AuditController::class, 'index'])->middleware('permission:audit.view')->name('audit');

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [A\SettingController::class, 'index'])->name('settings');
        Route::put('/settings', [A\SettingController::class, 'update'])->name('settings.update');
        Route::resource('categories', A\CategoryController::class)->except(['show', 'create', 'edit']);
        Route::get('/languages', [A\LanguageController::class, 'index'])->name('languages.index');
        Route::post('/languages', [A\LanguageController::class, 'store'])->name('languages.store');
        Route::put('/languages/{language}', [A\LanguageController::class, 'update'])->name('languages.update');
        Route::get('/languages/{language}/translations', [A\LanguageController::class, 'translations'])->name('languages.translations');
        Route::post('/languages/{language}/translations', [A\LanguageController::class, 'saveTranslation'])->name('languages.translations.save');
        Route::post('/announcements', [A\SettingController::class, 'announce'])->name('announcements.store');
        Route::get('/announcements', [A\SettingController::class, 'announcements'])->name('announcements.index');
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [A\RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles', [A\RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [A\RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [A\RoleController::class, 'destroy'])->name('roles.destroy');
        Route::post('/permissions', [A\RoleController::class, 'storePermission'])->name('permissions.store');
    });

    Route::middleware('permission:elections.manage')->group(function () {
        Route::get('/elections', [A\ElectionController::class, 'index'])->name('elections.index');
        Route::post('/elections', [A\ElectionController::class, 'store'])->name('elections.store');
        Route::put('/elections/{election}', [A\ElectionController::class, 'update'])->name('elections.update');
        Route::delete('/elections/{election}', [A\ElectionController::class, 'destroy'])->name('elections.destroy');
        Route::post('/elections/{election}/results', [A\ElectionController::class, 'storeResult'])->name('elections.results.store');
        Route::delete('/election-results/{result}', [A\ElectionController::class, 'destroyResult'])->name('elections.results.destroy');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/newsletter', [A\GrowthController::class, 'newsletter'])->name('newsletter.index');
        Route::post('/newsletter', [A\GrowthController::class, 'sendNewsletter'])->name('newsletter.send');
        Route::get('/ambassadors', [A\GrowthController::class, 'ambassadors'])->name('ambassadors.index');
        Route::post('/ambassadors/{ambassador}', [A\GrowthController::class, 'decideAmbassador'])->name('ambassadors.decide');
    });
});
