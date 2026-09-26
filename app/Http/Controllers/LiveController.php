<?php

namespace App\Http\Controllers;

use App\Models\Live;
use App\Models\LiveParticipant;
use App\Models\LiveReaction;
use App\Models\LiveSignal;
use App\Models\LiveViewer;
use App\Models\Reminder;
use App\Models\Upload;
use App\Models\User;
use App\Models\Video;
use App\Services\MediaService;
use App\Services\Notifier;
use App\Services\TranscriptionService;
use App\Services\VideoProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Vwajèn Live : diffusion depuis le navigateur (WebRTC, signalisation par la base de données),
 * lives à plusieurs (co-hôtes), ou flux HLS externe (OBS / serveur média).
 * Seuls les comptes certifiés peuvent démarrer ou programmer un live.
 */
class LiveController extends Controller
{
    public const REACTIONS = ['❤️', '👏', '🔥', '😂', '😮', '🇭🇹'];

    public function __construct(private Notifier $notifier) {}

    public function index(Request $request)
    {
        $viewer = $request->user();
        $base = fn () => Live::visibleTo($viewer)->where('kind', 'video')->with('user');

        return view('lives.index', [
            'live' => $base()->where('status', 'live')->latest('started_at')->get(),
            'upcoming' => $base()->where('status', 'scheduled')->where('scheduled_at', '>=', now()->subHour())->orderBy('scheduled_at')->limit(12)->get(),
            'replays' => $base()->where('status', 'ended')->whereNotNull('replay_video_id')->with('replay')->latest('ended_at')->paginate(12),
            'kind' => 'video',
        ]);
    }

    /** Audio Spaces (lives audio). */
    public function spaces(Request $request)
    {
        $viewer = $request->user();
        $base = fn () => Live::visibleTo($viewer)->where('kind', 'audio')->with('user');

        return view('lives.index', [
            'live' => $base()->where('status', 'live')->latest('started_at')->get(),
            'upcoming' => $base()->where('status', 'scheduled')->orderBy('scheduled_at')->limit(12)->get(),
            'replays' => $base()->where('status', 'ended')->whereNotNull('replay_video_id')->with('replay')->latest('ended_at')->paginate(12),
            'kind' => 'audio',
        ]);
    }

    public function show(Request $request, Live $live)
    {
        $user = $request->user();
        abort_if($live->is_hidden && ! $user?->isStaff() && ! $live->isHost($user), 404);
        $live->load(['user', 'participants.user', 'replay.subtitles', 'debate']);

        $banned = $user && LiveViewer::where('live_id', $live->id)->where('user_id', $user->id)->where('is_banned', true)->exists();
        $isHost = $live->isHost($user);
        $canModerate = $live->canModerate($user);
        $invitation = $user ? $live->participants->where('user_id', $user->id)->where('status', 'invited')->first() : null;
        $reminded = $user && Reminder::where(['user_id' => $user->id, 'remindable_type' => 'live', 'remindable_id' => $live->id])->exists();

        $rtc = [
            'iceServers' => array_values(array_filter([
                ['urls' => config('vwajen.webrtc.stun')],
                config('vwajen.webrtc.turn_url') ? ['urls' => config('vwajen.webrtc.turn_url'), 'username' => config('vwajen.webrtc.turn_username'),
                    'credential' => config('vwajen.webrtc.turn_credential')] : null,
            ])),
        ];

        return view('lives.show', compact('live', 'banned', 'isHost', 'canModerate', 'invitation', 'reminded', 'rtc') + ['reactions' => self::REACTIONS]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->canGoLive(), 403, __('Seuls les comptes certifiés peuvent faire des lives.'));

        return view('lives.form', ['live' => new Live(['kind' => $request->query('kind') === 'audio' ? 'audio' : 'video', 'chat_enabled' => true])]);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'kind' => ['required', 'in:video,audio'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'playback_url' => ['nullable', 'url', 'max:2048'],
            'chat_enabled' => ['nullable', 'boolean'],
            'chat_followers_only' => ['nullable', 'boolean'],
            'slow_mode_seconds' => ['nullable', 'integer', 'min:0', 'max:300'],
            'city' => ['nullable', 'string', 'max:100'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function store(Request $request, MediaService $media)
    {
        $user = $request->user();
        abort_unless($user->canGoLive(), 403, __('Seuls les comptes certifiés peuvent faire des lives.'));
        $data = $request->validate($this->rules());
        if ($request->hasFile('cover')) {
            $data['cover'] = $media->storeImage($request->file('cover'), 'lives', 1280);
        }

        $live = Live::create($data + [
            'user_id' => $user->id,
            'stream_key' => Str::random(40),
            'status' => 'scheduled',
            'country' => $user->country,
            'chat_enabled' => $request->boolean('chat_enabled', true),
            'chat_followers_only' => $request->boolean('chat_followers_only'),
        ]);

        if ($live->scheduled_at) {
            $this->notifier->broadcast($this->notifier->followersOf($user), 'live', $user,
                ':name a programmé un live : « :title »', ['name' => $user->name, 'title' => $live->title], $live->url(), $live);

            return redirect()->route('lives.show', $live)->with('status', __('Live programmé.'));
        }

        return redirect()->route('lives.show', $live)->with('status', __('Votre studio est prêt. Démarrez le live quand vous voulez.'));
    }

    public function edit(Request $request, Live $live)
    {
        abort_unless($live->user_id === $request->user()->id, 403);

        return view('lives.form', compact('live'));
    }

    public function update(Request $request, Live $live, MediaService $media)
    {
        abort_unless($live->user_id === $request->user()->id, 403);
        $rules = $this->rules();
        $rules['scheduled_at'] = ['nullable', 'date'];
        $data = $request->validate($rules);
        if ($request->hasFile('cover')) {
            $media->delete($live->cover);
            $data['cover'] = $media->storeImage($request->file('cover'), 'lives', 1280);
        }
        $data['chat_enabled'] = $request->boolean('chat_enabled');
        $data['chat_followers_only'] = $request->boolean('chat_followers_only');
        $live->update($data);

        return redirect()->route('lives.show', $live)->with('status', __('Live mis à jour.'));
    }

    public function destroy(Request $request, Live $live)
    {
        abort_unless($live->user_id === $request->user()->id || $request->user()->hasPermission('content.delete'), 403);
        $live->delete();

        return redirect()->route('lives.index')->with('status', __('Live supprimé.'));
    }

    public function start(Request $request, Live $live)
    {
        $user = $request->user();
        abort_unless($live->user_id === $user->id && $user->canGoLive(), 403);
        abort_unless(in_array($live->status, ['scheduled', 'ended'], true), 422);

        $live->update(['status' => 'live', 'started_at' => now(), 'ended_at' => null]);
        $live->debate?->update(['status' => 'live']);

        $this->notifier->broadcast($this->notifier->followersOf($user), 'live', $user,
            ':name est en direct : « :title »', ['name' => $user->name, 'title' => $live->title], $live->url(), $live);
        $reminded = User::whereIn('id', Reminder::where(['remindable_type' => 'live', 'remindable_id' => $live->id])->whereNull('sent_at')->select('user_id'));
        $this->notifier->broadcast($reminded, 'live', $user, 'Le live « :title » commence maintenant', ['title' => $live->title], $live->url(), $live);
        Reminder::where(['remindable_type' => 'live', 'remindable_id' => $live->id])->update(['sent_at' => now()]);

        return $this->reply($request, ['status' => 'live'], __('Vous êtes en direct !'));
    }

    public function end(Request $request, Live $live)
    {
        abort_unless($live->user_id === $request->user()->id || $live->canModerate($request->user()), 403);
        $live->update(['status' => 'ended', 'ended_at' => now(), 'peak_viewers' => max($live->peak_viewers, $live->currentViewersCount())]);
        $live->debate?->update(['status' => 'ended']);
        LiveSignal::where('live_id', $live->id)->delete();

        return $this->reply($request, ['status' => 'ended'], __('Live terminé.'));
    }

    public function cancel(Request $request, Live $live)
    {
        abort_unless($live->user_id === $request->user()->id, 403);
        $live->update(['status' => 'cancelled']);

        return back()->with('status', __('Live annulé.'));
    }

    /** Inviter des participants (live à plusieurs) ou un modérateur du chat. */
    public function invite(Request $request, Live $live)
    {
        abort_unless($live->user_id === $request->user()->id, 403);
        $data = $request->validate(['username' => ['required', 'string'], 'role' => ['required', 'in:guest,moderator']]);
        $guest = User::where('username', ltrim($data['username'], '@'))->where('status', 'active')->firstOrFail();
        abort_if($guest->id === $live->user_id, 422);
        abort_if($data['role'] === 'guest' && $live->participants()->where('role', 'guest')->where('status', 'accepted')->count() >= 3, 422, __('Maximum 3 invités simultanés.'));

        LiveParticipant::updateOrCreate(['live_id' => $live->id, 'user_id' => $guest->id], ['role' => $data['role'], 'status' => 'invited']);
        $this->notifier->send($guest, 'live', $request->user(), ':name vous invite à rejoindre son live « :title »',
            ['name' => $request->user()->name, 'title' => $live->title], $live->url(), $live);

        return back()->with('status', __('Invitation envoyée à :name.', ['name' => $guest->name]));
    }

    public function respond(Request $request, Live $live)
    {
        $p = LiveParticipant::where('live_id', $live->id)->where('user_id', $request->user()->id)->firstOrFail();
        $accept = $request->input('answer') === 'accept';
        $p->update(['status' => $accept ? 'accepted' : 'declined']);

        return back()->with('status', $accept ? __('Vous avez rejoint le live.') : __('Invitation refusée.'));
    }

    /** Présence : enregistre le pair, renvoie l'état du live, les diffuseurs actifs et les réactions récentes. */
    public function heartbeat(Request $request, Live $live)
    {
        $data = $request->validate([
            'peer_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'publish' => ['nullable', 'boolean'],
            'after_reaction' => ['nullable', 'integer'],
        ]);
        $user = $request->user();
        // Les identifiants de pair autorisés pour cette session (un par onglet, 5 maximum).
        $sessionKey = "live.{$live->id}.peers";
        $peers = array_values(array_unique(array_merge($request->session()->get($sessionKey, []), [$data['peer_id']])));
        $request->session()->put($sessionKey, array_slice($peers, -5));

        if ($user && LiveViewer::where('live_id', $live->id)->where('user_id', $user->id)->where('is_banned', true)->exists()) {
            return response()->json(['kicked' => true, 'banned' => true]);
        }

        $publish = $request->boolean('publish') && $live->isHost($user) && $live->isLive();
        $viewer = LiveViewer::firstOrNew(['live_id' => $live->id, 'peer_id' => $data['peer_id']]);
        if ($viewer->kicked_at) {
            return response()->json(['kicked' => true]);
        }
        $isNew = ! $viewer->exists;
        $viewer->fill(['user_id' => $user?->id, 'last_seen_at' => now(), 'is_publisher' => $publish])->save();

        $current = $live->currentViewersCount();
        $updates = [];
        if ($isNew && ! $publish) {
            $updates['total_viewers'] = $live->total_viewers + 1;
        }
        if ($current > $live->peak_viewers) {
            $updates['peak_viewers'] = $current;
        }
        if ($updates) {
            $live->update($updates);
        }

        $hosts = LiveViewer::where('live_id', $live->id)->where('is_publisher', true)->where('peer_id', '!=', $data['peer_id'])
            ->where('last_seen_at', '>=', now()->subSeconds(15))->with('user:id,name,username')->get()
            ->map(fn ($v) => ['peer_id' => $v->peer_id, 'name' => $v->user?->name]);

        $reactions = LiveReaction::where('live_id', $live->id)->where('id', '>', (int) ($data['after_reaction'] ?? 0))
            ->where('created_at', '>=', now()->subSeconds(20))->orderBy('id')->limit(50)->get(['id', 'emoji']);

        $payload = [
            'status' => $live->status,
            'viewers' => $current,
            'hosts' => $hosts,
            'reactions' => $reactions,
            'likes' => $live->likes_count,
        ];
        if ($live->canModerate($user)) {
            $payload['audience'] = LiveViewer::where('live_id', $live->id)->where('last_seen_at', '>=', now()->subSeconds(30))
                ->whereNull('kicked_at')->where('is_banned', false)->with('user:id,name,username')->limit(200)->get()
                ->map(fn ($v) => ['id' => $v->id, 'name' => $v->user?->name ?? __('Invité'), 'username' => $v->user?->username]);
        }

        return response()->json($payload);
    }

    public function signals(Request $request, Live $live)
    {
        $peer = (string) $request->query('peer_id');
        abort_unless(in_array($peer, $request->session()->get("live.{$live->id}.peers", []), true), 403);
        $signals = LiveSignal::where('live_id', $live->id)->where('to_peer', $peer)->where('id', '>', (int) $request->query('after', 0))
            ->orderBy('id')->limit(100)->get(['id', 'from_peer', 'type', 'payload']);
        LiveSignal::where('live_id', $live->id)->where('created_at', '<', now()->subMinutes(2))->delete();

        return response()->json($signals);
    }

    public function signal(Request $request, Live $live)
    {
        $data = $request->validate([
            'from' => ['required', 'string', 'max:64'],
            'to' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'type' => ['required', 'in:offer,answer,candidate,bye'],
            'payload' => ['required', 'string', 'max:20000'],
        ]);
        abort_unless(in_array($data['from'], $request->session()->get("live.{$live->id}.peers", []), true), 403);
        abort_unless(LiveViewer::where('live_id', $live->id)->where('peer_id', $data['to'])->exists(), 404);

        LiveSignal::create(['live_id' => $live->id, 'from_peer' => $data['from'], 'to_peer' => $data['to'], 'type' => $data['type'],
            'payload' => $data['payload'], 'created_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function react(Request $request, Live $live)
    {
        $data = $request->validate(['emoji' => ['required', 'in:'.implode(',', self::REACTIONS)]]);
        LiveReaction::create(['live_id' => $live->id, 'user_id' => $request->user()->id, 'emoji' => $data['emoji'], 'created_at' => now()]);

        return response()->json(['ok' => true]);
    }

    /** Expulser un spectateur (il peut revenir plus tard). */
    public function kick(Request $request, Live $live, LiveViewer $viewer)
    {
        abort_unless($live->canModerate($request->user()) && $viewer->live_id === $live->id, 403);
        $viewer->update(['kicked_at' => now()]);

        return $this->reply($request, ['ok' => true], __('Spectateur expulsé.'));
    }

    /** Bloquer un spectateur (ne peut plus revenir sur ce live). */
    public function ban(Request $request, Live $live, LiveViewer $viewer)
    {
        abort_unless($live->canModerate($request->user()) && $viewer->live_id === $live->id, 403);
        $viewer->update(['kicked_at' => now(), 'is_banned' => true]);
        if ($viewer->user_id) {
            LiveViewer::where('live_id', $live->id)->where('user_id', $viewer->user_id)->update(['is_banned' => true, 'kicked_at' => now()]);
        }

        return $this->reply($request, ['ok' => true], __('Spectateur bloqué.'));
    }

    public function chatSettings(Request $request, Live $live)
    {
        abort_unless($live->canModerate($request->user()), 403);
        $data = $request->validate(['chat_enabled' => ['nullable', 'boolean'], 'chat_followers_only' => ['nullable', 'boolean'],
            'slow_mode_seconds' => ['nullable', 'integer', 'min:0', 'max:300']]);
        $live->update(['chat_enabled' => $request->boolean('chat_enabled'), 'chat_followers_only' => $request->boolean('chat_followers_only'),
            'slow_mode_seconds' => (int) ($data['slow_mode_seconds'] ?? 0)]);

        return $this->reply($request, ['ok' => true], __('Paramètres du chat mis à jour.'));
    }

    public function remind(Request $request, Live $live)
    {
        abort_unless($live->status === 'scheduled' && $live->scheduled_at, 422);
        $attrs = ['user_id' => $request->user()->id, 'remindable_type' => 'live', 'remindable_id' => $live->id];
        $existing = Reminder::where($attrs)->first();
        $existing ? $existing->delete() : Reminder::create($attrs + ['remind_at' => $live->scheduled_at->copy()->subMinutes(10)]);

        return $this->reply($request, ['active' => ! $existing], $existing ? __('Rappel supprimé.') : __('Vous serez notifié avant le début.'));
    }

    /** Replay : l'enregistrement du navigateur (ou un fichier) est publié comme vidéo. */
    public function replay(Request $request, Live $live, MediaService $media)
    {
        $user = $request->user();
        abort_unless($live->user_id === $user->id, 403);
        $data = $request->validate([
            'upload' => ['nullable', 'uuid', 'required_without:file'],
            'file' => ['nullable', 'file', 'mimes:webm,mp4,mov,m4a,ogg,mp3', 'max:'.(config('vwajen.limits.video_mb') * 1024)],
        ]);
        $path = $request->hasFile('file')
            ? $media->storeFile($request->file('file'), 'replays/'.date('Y/m'))
            : $media->adoptUpload(Upload::where('uuid', $data['upload'])->where('user_id', $user->id)->firstOrFail(), 'replays/'.date('Y/m'));

        $video = Video::create([
            'user_id' => $user->id, 'kind' => 'replay', 'title' => $live->title, 'description' => $live->description,
            'path' => $path, 'thumbnail' => $live->cover, 'processing_status' => 'pending',
            'source_type' => 'live', 'source_id' => $live->id, 'country' => $live->country,
        ]);
        $live->update(['replay_video_id' => $video->id]);
        $live->debate?->update(['replay_video_id' => $video->id]);
        dispatch(function () use ($video, $live) {
            app(VideoProcessor::class)->process($video);
            // Transcription automatique du live (si configurée) : transcript + sous-titres automatiques du replay.
            $transcriber = app(TranscriptionService::class);
            if ($transcriber->enabled() && ($r = $transcriber->transcribeFile(Storage::disk('public')->path($video->path)))) {
                $live->update(['transcript' => $r['text']]);
                $video->update(['transcript' => $r['text']]);
                $path = 'subtitles/'.$video->id.'-auto.vtt';
                Storage::disk('public')->put($path, $r['vtt']);
                $video->subtitles()->create(['lang' => $video->lang ?? 'ht', 'label' => __('Sous-titres').' (auto)', 'path' => $path, 'is_auto' => true]);
            }
        })->afterResponse();

        return $this->reply($request, ['video' => $video->id, 'url' => $video->url()], __('Replay publié.'));
    }
}
