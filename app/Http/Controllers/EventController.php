<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Community;
use App\Models\Event;
use App\Models\EventRsvp;
use App\Models\Reminder;
use App\Models\User;
use App\Services\AntiSpam;
use App\Services\ContentService;
use App\Services\MediaService;
use App\Services\Notifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Événements : date, heure, fuseau horaire, lieu, carte, en ligne, RSVP, rappels, notifications, partage. */
class EventController extends Controller
{
    public function __construct(private Notifier $notifier) {}

    public function index(Request $request)
    {
        $viewer = $request->user();
        $when = $request->query('when') === 'past' ? 'past' : 'upcoming';
        $q = Event::visibleTo($viewer)->with(['user', 'category'])
            ->when($request->query('department'), fn ($q, $d) => $q->where('department', $d))
            ->when($request->query('city'), fn ($q, $c) => $q->where('city', 'like', "%$c%"))
            ->when($request->query('country'), fn ($q, $c) => $q->where('country', $c))
            ->when($request->query('category'), fn ($q, $c) => $q->whereHas('category', fn ($x) => $x->where('slug', $c)))
            ->when($request->query('online') !== null && $request->query('online') !== '', fn ($q) => $q->where('is_online', $request->boolean('online')));
        $events = ($when === 'past' ? $q->past() : $q->upcoming())->paginate(18)->withQueryString();

        return view('events.index', ['events' => $events, 'when' => $when, 'categories' => Category::allActive()]);
    }

    public function show(Request $request, Event $event)
    {
        $viewer = $request->user();
        abort_if($event->is_hidden && ! $event->canManage($viewer) && ! $viewer?->isStaff(), 404);
        $event->load(['user', 'category', 'community']);
        $myRsvp = $event->rsvpOf($viewer);
        $attendees = User::whereIn('id', $event->rsvps()->where('status', 'going')->select('user_id'))->limit(24)->get();
        $reminded = $viewer && Reminder::where(['user_id' => $viewer->id, 'remindable_type' => 'event', 'remindable_id' => $event->id])->exists();
        $comments = $event->rootComments()->where('is_hidden', false)->with(['user', 'replies.user'])
            ->withExists(['likes as liked' => fn ($q) => $q->where('user_id', $viewer?->id)])->latest()->paginate(20);

        return view('events.show', compact('event', 'myRsvp', 'attendees', 'reminded', 'comments'));
    }

    public function create(Request $request)
    {
        return view('events.form', ['event' => new Event(['timezone' => $request->user()->country === 'HT' ? 'America/Port-au-Prince' : config('app.timezone'),
            'country' => $request->user()->country, 'community_id' => $request->query('community')]),
            'categories' => Category::allActive(), 'communities' => $request->user()->communities()->get()]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'is_online' => ['nullable', 'boolean'],
            'online_url' => ['nullable', 'required_if:is_online,1', 'url', 'max:2048'],
            'location_name' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'in:'.implode(',', array_keys(config('vwajen.departments')))],
            'country' => ['required', 'string', 'size:2'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'community_id' => ['nullable', 'exists:communities,id'],
            'visibility' => ['nullable', 'in:public,followers'],
        ]);

        // Les heures saisies sont dans le fuseau de l'événement ; stockage en UTC.
        $start = Carbon::parse($data['start_date'].' '.$data['start_time'], $data['timezone'])->utc();
        $end = ! empty($data['end_date']) ? Carbon::parse($data['end_date'].' '.($data['end_time'] ?? '23:59'), $data['timezone'])->utc() : null;
        if ($end && $end->lt($start)) {
            throw ValidationException::withMessages(['end_date' => __('La fin doit être après le début.')]);
        }
        if (! empty($data['community_id'])) {
            abort_unless(Community::findOrFail($data['community_id'])->isMember($request->user()), 403);
        }
        $data['starts_at'] = $start;
        $data['ends_at'] = $end;
        $data['is_online'] = $request->boolean('is_online');
        $data['country'] = strtoupper($data['country']);
        unset($data['start_date'], $data['start_time'], $data['end_date'], $data['end_time']);

        return $data;
    }

    public function store(Request $request, MediaService $media, AntiSpam $spam, ContentService $content)
    {
        $user = $request->user();
        $spam->ensureCanPublish($user);
        $data = $this->validated($request);
        $spam->checkContent($user, $data['title'].' '.($data['description'] ?? ''), 'title');
        if ($request->hasFile('cover')) {
            $data['cover'] = $media->storeImage($request->file('cover'), 'events', 1600);
        }
        $event = Event::create($data + ['user_id' => $user->id]);
        EventRsvp::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => 'going']);
        $event->increment('going_count');
        $content->syncAll($event, $event->title.' '.$event->description, $user);

        $this->notifier->broadcast($this->notifier->followersOf($user), 'event', $user,
            ':name organise un événement : « :title »', ['name' => $user->name, 'title' => $event->title], $event->url(), $event);

        return redirect()->route('events.show', $event)->with('status', __('Événement créé.'));
    }

    public function edit(Request $request, Event $event)
    {
        abort_unless($event->canManage($request->user()), 403);

        return view('events.form', ['event' => $event, 'categories' => Category::allActive(), 'communities' => $request->user()->communities()->get()]);
    }

    public function update(Request $request, Event $event, MediaService $media)
    {
        abort_unless($event->canManage($request->user()), 403);
        $data = $this->validated($request);
        if ($request->hasFile('cover')) {
            $media->delete($event->cover);
            $data['cover'] = $media->storeImage($request->file('cover'), 'events', 1600);
        }
        $event->update($data);

        if ($event->wasChanged(['starts_at', 'location_name', 'address', 'online_url', 'is_online'])) {
            $attendees = User::whereIn('id', $event->rsvps()->whereIn('status', ['going', 'interested'])->select('user_id'));
            $this->notifier->broadcast($attendees, 'event', $request->user(), 'L\'événement « :title » a été modifié', ['title' => $event->title], $event->url(), $event);
            Reminder::where(['remindable_type' => 'event', 'remindable_id' => $event->id])->whereNull('sent_at')
                ->update(['remind_at' => $event->starts_at->copy()->subHour()]);
        }

        return redirect()->route('events.show', $event)->with('status', __('Événement mis à jour.'));
    }

    public function destroy(Request $request, Event $event)
    {
        abort_unless($event->canManage($request->user()), 403);
        $attendees = User::whereIn('id', $event->rsvps()->whereIn('status', ['going', 'interested'])->where('user_id', '!=', $event->user_id)->select('user_id'));
        $this->notifier->broadcast($attendees, 'event', $request->user(), 'L\'événement « :title » a été annulé', ['title' => $event->title], route('events.index'), $event);
        $event->delete();

        return redirect()->route('events.index')->with('status', __('Événement supprimé.'));
    }

    public function rsvp(Request $request, Event $event)
    {
        $data = $request->validate(['status' => ['required', 'in:going,interested,not_going']]);
        abort_if($event->isPast(), 422, __('Cet événement est terminé.'));
        if ($data['status'] === 'going' && $event->capacity && $event->going_count >= $event->capacity && $event->rsvpOf($request->user()) !== 'going') {
            abort(422, __('Complet.'));
        }
        $user = $request->user();
        $previous = EventRsvp::where('event_id', $event->id)->where('user_id', $user->id)->first();
        if ($previous) {
            $previous->status !== 'not_going' && $event->{$previous->status.'_count'} > 0 && $event->decrement($previous->status.'_count');
            $previous->update(['status' => $data['status']]);
        } else {
            EventRsvp::create(['event_id' => $event->id, 'user_id' => $user->id, 'status' => $data['status']]);
        }
        if ($data['status'] !== 'not_going') {
            $event->increment($data['status'].'_count');
            Reminder::firstOrCreate(['user_id' => $user->id, 'remindable_type' => 'event', 'remindable_id' => $event->id,
                'remind_at' => $event->starts_at->copy()->subHour()]);
            if (! $previous && $data['status'] === 'going') {
                $this->notifier->send($event->user, 'event', $user, ':name participera à « :title »', ['name' => $user->name, 'title' => $event->title], $event->url(), $event);
            }
        } else {
            Reminder::where(['user_id' => $user->id, 'remindable_type' => 'event', 'remindable_id' => $event->id])->delete();
        }

        return $this->reply($request, ['status' => $data['status']], __('Réponse enregistrée.'));
    }

    public function remind(Request $request, Event $event)
    {
        $data = $request->validate(['minutes' => ['required', 'integer', 'in:10,60,1440']]);
        $at = $event->starts_at->copy()->subMinutes((int) $data['minutes']);
        abort_unless($at->isFuture(), 422, __('Trop tard pour ce rappel.'));
        Reminder::firstOrCreate(['user_id' => $request->user()->id, 'remindable_type' => 'event', 'remindable_id' => $event->id, 'remind_at' => $at]);

        return $this->reply($request, ['ok' => true], __('Rappel programmé.'));
    }

    /** Export calendrier (.ics). */
    public function ics(Event $event)
    {
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');
        $esc = fn ($s) => str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\,', '\n'], (string) $s);
        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Vwajen//FR', 'BEGIN:VEVENT',
            'UID:event-'.$event->id.'@'.parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:'.$fmt(now()), 'DTSTART:'.$fmt($event->starts_at), 'DTEND:'.$fmt($event->ends_at ?? $event->starts_at->copy()->addHours(2)),
            'SUMMARY:'.$esc($event->title), 'DESCRIPTION:'.$esc(Str::limit($event->description, 500)."\n".$event->url()),
            'LOCATION:'.$esc($event->is_online ? $event->online_url : trim($event->location_name.' '.$event->address.' '.$event->city)),
            'URL:'.$event->url(), 'END:VEVENT', 'END:VCALENDAR',
        ]);

        return response($ics, 200, ['Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'attachment; filename="vwajen-event-'.$event->id.'.ics"']);
    }
}
