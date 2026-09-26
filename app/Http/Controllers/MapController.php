<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\Event;
use App\Models\Live;
use App\Models\User;
use Illuminate\Http\Request;

/** Vwajèn Map : carte interactive des événements et activités publiques, par zone, ville ou département. */
class MapController extends Controller
{
    public function index()
    {
        return view('map.index', ['departments' => config('vwajen.departments')]);
    }

    /** Données GeoJSON-like pour la carte (filtrées par zone visible, ville, département, type, période). */
    public function data(Request $request)
    {
        $data = $request->validate([
            'bounds' => ['nullable', 'string'], // south,west,north,east
            'department' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:events,lives,candidates,communities'],
            'period' => ['nullable', 'in:upcoming,today,week,past'],
        ]);
        $types = $data['types'] ?? ['events', 'lives'];
        $bounds = isset($data['bounds']) ? array_map('floatval', explode(',', $data['bounds'])) : null;
        $inBounds = function ($q, $lat = 'lat', $lng = 'lng') use ($bounds) {
            if ($bounds && count($bounds) === 4) {
                $q->whereBetween($lat, [$bounds[0], $bounds[2]])->whereBetween($lng, [$bounds[1], $bounds[3]]);
            }
        };
        $viewer = $request->user();
        $features = collect();

        if (in_array('events', $types, true)) {
            $q = Event::visibleTo($viewer)->whereNotNull('lat')->whereNotNull('lng');
            $inBounds($q);
            $q->when($data['department'] ?? null, fn ($q, $d) => $q->where('department', $d))
                ->when($data['city'] ?? null, fn ($q, $c) => $q->where('city', 'like', "%$c%"));
            match ($data['period'] ?? 'upcoming') {
                'today' => $q->whereDate('starts_at', today()),
                'week' => $q->whereBetween('starts_at', [now(), now()->addWeek()]),
                'past' => $q->where('starts_at', '<', now()),
                default => $q->where('starts_at', '>=', now()->subHours(6)),
            };
            foreach ($q->limit(500)->get() as $e) {
                $features->push(['type' => 'event', 'id' => $e->id, 'lat' => $e->lat, 'lng' => $e->lng, 'title' => $e->title,
                    'subtitle' => $e->localStart()->translatedFormat('d M Y, H:i').' · '.($e->city ?? ''), 'url' => $e->url()]);
            }
        }

        if (in_array('lives', $types, true)) {
            $q = Live::visibleTo($viewer)->whereIn('status', ['live', 'scheduled'])->whereNotNull('lat')->whereNotNull('lng');
            $inBounds($q);
            $q->when($data['city'] ?? null, fn ($q, $c) => $q->where('city', 'like', "%$c%"));
            foreach ($q->with('user')->limit(200)->get() as $l) {
                $features->push(['type' => 'live', 'id' => $l->id, 'lat' => (float) $l->lat, 'lng' => (float) $l->lng, 'title' => $l->title,
                    'subtitle' => $l->isLive() ? __('En direct') : ($l->scheduled_at?->translatedFormat('d M, H:i') ?? ''), 'url' => $l->url(), 'live' => $l->isLive()]);
            }
        }

        // Activités publiques agrégées par département (candidats, communautés)
        $departments = config('vwajen.departments');
        if (in_array('candidates', $types, true)) {
            $counts = User::where('account_type', 'candidate')->where('status', 'active')->whereNotNull('department')
                ->when($data['department'] ?? null, fn ($q, $d) => $q->where('department', $d))
                ->selectRaw('department, COUNT(*) c')->groupBy('department')->pluck('c', 'department');
            foreach ($counts as $dep => $c) {
                if (isset($departments[$dep])) {
                    $features->push(['type' => 'candidates', 'id' => $dep, 'lat' => $departments[$dep]['lat'], 'lng' => $departments[$dep]['lng'],
                        'title' => __(':n candidats', ['n' => $c]), 'subtitle' => $departments[$dep]['name'], 'url' => route('candidates.index', ['department' => $dep])]);
                }
            }
        }
        if (in_array('communities', $types, true)) {
            foreach (Community::where('is_hidden', false)->where('country', 'HT')->whereNotNull('city')->limit(200)->get() as $c) {
                $dep = collect($departments)->search(fn ($d) => mb_strtolower($d['capital']) === mb_strtolower($c->city));
                if ($dep) {
                    $features->push(['type' => 'community', 'id' => $c->id, 'lat' => $departments[$dep]['lat'] + 0.01, 'lng' => $departments[$dep]['lng'] + 0.01,
                        'title' => $c->name, 'subtitle' => __(':n membres', ['n' => $c->members_count]), 'url' => $c->url()]);
                }
            }
        }

        $byDepartment = Event::visibleTo($viewer)->where('starts_at', '>=', now())->whereNotNull('department')
            ->selectRaw('department, COUNT(*) c')->groupBy('department')->pluck('c', 'department');

        return response()->json(['features' => $features->values(), 'departments' => $byDepartment]);
    }
}
