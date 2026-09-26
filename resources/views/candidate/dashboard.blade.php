@extends('layouts.app')
@section('title', __('Tableau de bord'))
@section('layout', 'wide')
@php
    $sections = ['overview' => ['dashboard', __('Vue d\'ensemble')], 'stats' => ['chart', __('Statistiques')], 'analytics' => ['activity', __('Analytics avancés')], 'posts' => ['message', __('Publications')], 'videos' => ['film', __('Vidéos')],
        'shorts' => ['shorts', 'Shorts'], 'lives' => ['live', __('Lives')], 'questions' => ['question', __('Questions reçues')], 'program' => ['program', __('Programme')],
        'events' => ['calendar', __('Événements')], 'debates' => ['debate', __('Débats')], 'notifications' => ['bell', __('Notifications')], 'profile' => ['user', __('Gestion du profil')]];
@endphp
@section('content')
    <div class="page-head"><h1>{{ __('Tableau de bord') }}</h1>
        <div class="actions">
            @if($user->canGoLive())<a href="{{ route('lives.create') }}" class="btn btn-live btn-sm"><x-icon name="live"/>{{ __('Démarrer un live') }}</a>@endif
            <a href="{{ route('videos.create') }}" class="btn btn-outline btn-sm"><x-icon name="upload"/>{{ __('Vidéo') }}</a>
            <a href="{{ route('events.create') }}" class="btn btn-outline btn-sm"><x-icon name="calendar"/>{{ __('Événement') }}</a>
        </div>
    </div>

    @if($profile && $profile->status !== 'verified')
        <div class="alert alert-warning"><x-icon name="shield"/><div>{{ $profile->status === 'rejected' ? __('Votre vérification a été refusée. Consultez la raison et soumettez une nouvelle demande.') : __('Votre demande de vérification est en cours d\'examen.') }}
            <a href="{{ route('verification.index') }}">{{ __('Voir la vérification') }}</a></div></div>
    @endif
    @unless($user->canGoLive())
        <div class="alert alert-info"><x-icon name="live"/><div class="small">{{ __('Les lives sont réservés aux comptes certifiés. Ils seront disponibles dès la validation de votre compte.') }}</div></div>
    @endunless

    <div class="side-layout">
        <nav class="card settings-nav" style="padding:.4rem" aria-label="{{ __('Sections du tableau de bord') }}">
            @foreach($sections as $key => [$icon, $label])
                <a href="{{ route('candidate.dashboard', $key) }}" class="{{ $section === $key ? 'active' : '' }}"><x-icon name="{{ $icon }}"/>{{ $label }}
                    @if($key === 'questions' && $stats['questions_open'])<span class="badge badge-warning right">{{ $stats['questions_open'] }}</span>@endif</a>
            @endforeach
        </nav>

        <div>
            @if(in_array($section, ['overview', 'stats']))
                <div class="grid grid-4 mb">
                    <div class="stat"><div class="v">{{ short_number($stats['followers']) }}</div><div class="l">{{ __('Abonnés') }}</div><div class="d" style="color:var(--success)">+{{ $stats['new_followers_30'] }} / 30 j</div></div>
                    <div class="stat"><div class="v">{{ short_number($stats['profile_views']) }}</div><div class="l">{{ __('Vues du profil') }}</div></div>
                    <div class="stat"><div class="v">{{ short_number($stats['post_views'] + $stats['video_views']) }}</div><div class="l">{{ __('Vues des contenus') }}</div></div>
                    <div class="stat"><div class="v">{{ $stats['answers'] }}/{{ $stats['questions'] }}</div><div class="l">{{ __('Questions répondues') }}</div></div>
                    <div class="stat"><div class="v">{{ $stats['posts'] }}</div><div class="l">{{ __('Publications') }}</div><div class="d faint">{{ short_number($stats['post_likes']) }} {{ __('likes') }}</div></div>
                    <div class="stat"><div class="v">{{ $stats['videos'] }} / {{ $stats['shorts'] }}</div><div class="l">{{ __('Vidéos / Shorts') }}</div><div class="d faint">{{ short_number($stats['video_views']) }} {{ __('vues') }}</div></div>
                    <div class="stat"><div class="v">{{ $stats['lives'] }}</div><div class="l">{{ __('Lives') }}</div><div class="d faint">{{ short_number($stats['live_viewers']) }} {{ __('spectateurs') }}</div></div>
                    <div class="stat"><div class="v">{{ $stats['events'] }} / {{ $stats['debates'] }}</div><div class="l">{{ __('Événements / Débats') }}</div></div>
                </div>
                <div class="card"><div class="card-head"><h3>{{ __('Nouveaux abonnés (30 jours)') }}</h3></div><div class="card-body">
                    @php $series = []; for ($i = 29; $i >= 0; $i--) { $d = now()->subDays($i)->toDateString(); $series[$d] = (int) ($followersChart[$d] ?? 0); } @endphp
                    @include('partials.bar-chart', ['series' => $series, 'label' => __('Nouveaux abonnés')])
                </div></div>
                @if($section === 'overview')
                    <div class="grid grid-2">
                        <a href="{{ route('candidate.dashboard', 'questions') }}" class="card card-body" style="color:var(--text)"><x-icon name="question"/> <strong>{{ trans_choice(':n question ouverte|:n questions ouvertes', $stats['questions_open'], ['n' => $stats['questions_open']]) }}</strong><div class="small muted">{{ __('Répondez aux citoyens en texte ou en vidéo.') }}</div></a>
                        <a href="{{ route('candidate.dashboard', 'program') }}" class="card card-body" style="color:var(--text)"><x-icon name="program"/> <strong>{{ __('Gestion du programme') }}</strong><div class="small muted">{{ __('Propositions, sources, versions, documents.') }}</div></a>
                    </div>
                @endif
            @endif

            @if($section === 'analytics')
                <div class="row between mb"><span class="small muted">{{ __('Engagement, meilleurs contenus et audience.') }}</span>
                    <a href="{{ route('candidate.analytics.export') }}" class="btn btn-outline btn-sm"><x-icon name="download"/>{{ __('Exporter (CSV)') }}</a></div>
                <div class="grid grid-3 mb">
                    <div class="stat"><div class="v">{{ $engagementRate }} %</div><div class="l">{{ __('Taux d\'engagement') }}</div><div class="d faint">{{ __('interactions / vues') }}</div></div>
                    <div class="stat"><div class="v">{{ gmdate('H\h i', $watchTime) }}</div><div class="l">{{ __('Temps de visionnage total') }}</div></div>
                    <div class="stat"><div class="v">{{ array_sum($viewsDaily) }}</div><div class="l">{{ __('Vues (30 jours)') }}</div></div>
                </div>
                <div class="card"><div class="card-head"><h3>{{ __('Vues des contenus (30 jours)') }}</h3></div><div class="card-body">@include('partials.bar-chart', ['series' => $viewsDaily, 'label' => __('Vues')])</div></div>
                <div class="grid grid-2">
                    <div class="card"><div class="card-head"><h3>{{ __('Audience par pays') }}</h3></div><div class="card-body">
                        @forelse($audienceCountries as $c => $n)<div class="row between small"><span>{{ country_name($c) }}</span><b>{{ $n }}</b></div><div class="progress" style="margin:.25rem 0 .55rem"><span style="width:{{ $n * 100 / max(1, $audienceCountries->sum()) }}%"></span></div>@empty<span class="muted small">—</span>@endforelse
                    </div></div>
                    <div class="card"><div class="card-head"><h3>{{ __('Audience par département') }}</h3></div><div class="card-body">
                        @forelse($audienceDepartments as $d => $n)<div class="row between small"><span>{{ department_name($d) }}</span><b>{{ $n }}</b></div><div class="progress" style="margin:.25rem 0 .55rem"><span style="width:{{ $n * 100 / max(1, $audienceDepartments->sum()) }}%"></span></div>@empty<span class="muted small">—</span>@endforelse
                    </div></div>
                </div>
                <div class="card"><div class="card-head"><h3>{{ __('Meilleure heure de publication') }}</h3></div><div class="card-body">
                    @php $hours = []; for ($h = 0; $h < 24; $h++) { $hours[sprintf('%02dh', $h)] = round((float) ($byHour[$h] ?? 0), 1); } @endphp
                    @include('partials.bar-chart', ['series' => $hours, 'label' => __('Engagement moyen par heure'), 'color' => 'var(--success)', 'height' => 110])
                </div></div>
                <div class="card"><div class="card-head"><h3>{{ __('Publications les plus engageantes') }}</h3></div>
                    <div class="table-wrap"><table class="table"><tbody>
                        @forelse($topPosts as $p)<tr><td><a href="{{ $p->url() }}">{{ \Illuminate\Support\Str::limit($p->body ?: __('(média)'), 80) }}</a></td><td>👁 {{ $p->views_count }}</td><td>♥ {{ $p->likes_count }}</td><td>💬 {{ $p->comments_count }}</td><td>↻ {{ $p->reposts_count }}</td></tr>@empty<tr><td class="muted">—</td></tr>@endforelse
                    </tbody></table></div></div>
                @if($topVideos->isNotEmpty())<div class="grid grid-3">@foreach($topVideos as $v) @include('partials.video-tile', ['v' => $v, 'hideAuthor' => true]) @endforeach</div>@endif
            @endif

            @if($section === 'posts')
                <div class="card"><div class="card-head"><h3>{{ __('Publications') }}</h3><button class="btn btn-primary btn-sm" data-modal-open="compose-modal">{{ __('Publier') }}</button></div>
                    <div class="table-wrap"><table class="table"><thead><tr><th>{{ __('Texte') }}</th><th>{{ __('Date') }}</th><th>{{ __('Vues') }}</th><th>{{ __('Likes') }}</th><th>{{ __('Comm.') }}</th><th>{{ __('Reposts') }}</th><th></th></tr></thead><tbody>
                        @forelse($items as $p)
                            <tr><td style="max-width:320px"><a href="{{ $p->url() }}" class="truncate" style="display:block">{{ \Illuminate\Support\Str::limit($p->body ?: __('(média)'), 70) }}</a>
                                @if($p->is_hidden)<span class="badge badge-warning">{{ __('Masquée') }}</span>@endif</td>
                                <td class="small">{{ $p->created_at->format('d/m/Y') }}</td><td>{{ $p->views_count }}</td><td>{{ $p->likes_count }}</td><td>{{ $p->comments_count }}</td><td>{{ $p->reposts_count }}</td>
                                <td class="actions-cell"><a href="{{ route('posts.edit', $p) }}" class="btn btn-ghost btn-sm">{{ __('Modifier') }}</a></td></tr>
                        @empty <tr><td colspan="7" class="muted">{{ __('Aucune publication.') }}</td></tr> @endforelse
                    </tbody></table></div></div>{{ $items->links() }}
            @endif

            @if(in_array($section, ['videos', 'shorts']))
                <div class="card"><div class="card-head"><h3>{{ $section === 'shorts' ? 'Shorts' : __('Gestion des vidéos') }}</h3><a href="{{ route('videos.create', ['kind' => $section === 'shorts' ? 'short' : 'long']) }}" class="btn btn-primary btn-sm">{{ __('Ajouter') }}</a></div>
                    <div class="table-wrap"><table class="table"><thead><tr><th>{{ __('Titre') }}</th><th>{{ __('Statut') }}</th><th>{{ __('Vues') }}</th><th>{{ __('Likes') }}</th><th>{{ __('Sous-titres') }}</th><th></th></tr></thead><tbody>
                        @forelse($items as $v)
                            <tr><td><a href="{{ $v->url() }}">{{ $v->title ?: __('Sans titre') }}</a></td><td><span class="badge">{{ __($v->visibility) }}</span> @if($v->processing_status !== 'ready')<span class="badge badge-warning">{{ __($v->processing_status) }}</span>@endif</td>
                                <td>{{ $v->views_count }}</td><td>{{ $v->likes_count }}</td><td>{{ $v->subtitles()->count() }}</td>
                                <td class="actions-cell"><a href="{{ route('videos.edit', $v) }}" class="btn btn-ghost btn-sm">{{ __('Gérer') }}</a></td></tr>
                        @empty <tr><td colspan="6" class="muted">{{ __('Aucune vidéo.') }}</td></tr> @endforelse
                    </tbody></table></div></div>{{ $items->links() }}
            @endif

            @if($section === 'lives')
                <div class="card"><div class="card-head"><h3>{{ __('Gestion des lives') }}</h3>@if($user->canGoLive())<a href="{{ route('lives.create') }}" class="btn btn-live btn-sm">{{ __('Nouveau live') }}</a>@endif</div>
                    <div class="table-wrap"><table class="table"><thead><tr><th>{{ __('Titre') }}</th><th>{{ __('Statut') }}</th><th>{{ __('Date') }}</th><th>{{ __('Pic') }}</th><th>{{ __('Total') }}</th><th>{{ __('Replay') }}</th><th></th></tr></thead><tbody>
                        @forelse($items as $l)
                            <tr><td><a href="{{ $l->url() }}">{{ $l->title }}</a></td><td><span class="badge {{ $l->isLive() ? 'badge-live' : '' }}">{{ __($l->status) }}</span></td>
                                <td class="small">{{ ($l->started_at ?? $l->scheduled_at)?->format('d/m/Y H:i') }}</td><td>{{ $l->peak_viewers }}</td><td>{{ $l->total_viewers }}</td>
                                <td>{!! $l->replay_video_id ? '<span class="badge badge-success">✓</span>' : '—' !!}</td>
                                <td class="actions-cell"><a href="{{ route('lives.edit', $l) }}" class="btn btn-ghost btn-sm">{{ __('Modifier') }}</a></td></tr>
                        @empty <tr><td colspan="7" class="muted">{{ __('Aucun live.') }}</td></tr> @endforelse
                    </tbody></table></div></div>{{ $items->links() }}
            @endif

            @if($section === 'questions')
                <div class="card"><div class="card-head"><h3>{{ __('Questions reçues') }}</h3>
                    <div class="pills"><a href="?" class="pill {{ ! request('status') ? 'active' : '' }}">{{ __('Toutes') }}</a><a href="?status=open" class="pill {{ request('status') === 'open' ? 'active' : '' }}">{{ __('Ouvertes') }}</a><a href="?status=answered" class="pill {{ request('status') === 'answered' ? 'active' : '' }}">{{ __('Répondues') }}</a></div></div>
                    @forelse($items as $q)
                        <div class="item"><div class="item-body">
                            <a href="{{ $q->url() }}" style="font-weight:700;color:var(--text)">{{ $q->title }}</a>
                            <div class="small muted">{{ $q->user->name }} · {{ $q->created_at->diffForHumans() }} · <b>{{ $q->supports_count }}</b> {{ __('soutiens') }}</div>
                        </div><span class="badge {{ $q->status === 'answered' ? 'badge-success' : 'badge-warning' }}">{{ $q->statusLabel() }}</span>
                            @if($q->status === 'open')<a href="{{ $q->url() }}#answer-form" class="btn btn-primary btn-sm">{{ __('Répondre') }}</a>@endif</div>
                    @empty <div class="empty"><p>{{ __('Aucune question.') }}</p></div> @endforelse
                </div>{{ $items->links() }}
            @endif

            @if($section === 'program')
                <div class="card"><div class="card-head"><h3>{{ __('Gestion du programme') }}</h3><a href="{{ route('programs.create') }}" class="btn btn-primary btn-sm"><x-icon name="plus"/>{{ __('Nouveau programme') }}</a></div>
                    @forelse($programs as $p)
                        <div class="item"><x-icon name="program"/><div class="item-body"><a href="{{ route('programs.edit', $p) }}" style="font-weight:700;color:var(--text)">{{ $p->title }}</a>
                            <div class="small muted">{{ __($p->status) }} · {{ trans_choice(':n version|:n versions', $p->versions_count, ['n' => $p->versions_count]) }} · {{ __('mis à jour') }} {{ $p->updated_at->diffForHumans() }}</div></div>
                            <a href="{{ $p->url() }}" class="btn btn-ghost btn-sm">{{ __('Voir') }}</a><a href="{{ route('programs.edit', $p) }}" class="btn btn-soft btn-sm">{{ __('Gérer') }}</a></div>
                    @empty <div class="empty"><x-icon name="program"/><p>{{ __('Vous n\'avez pas encore de programme.') }}</p><a href="{{ route('programs.create') }}" class="btn btn-primary">{{ __('Publier un programme') }}</a></div> @endforelse
                </div>
            @endif

            @if($section === 'events')
                <div class="card"><div class="card-head"><h3>{{ __('Événements') }}</h3><a href="{{ route('events.create') }}" class="btn btn-primary btn-sm">{{ __('Créer') }}</a></div>
                    @forelse($items as $e) @include('partials.event-row', ['e' => $e]) @empty <div class="empty"><p>{{ __('Aucun événement.') }}</p></div> @endforelse
                </div>{{ $items->links() }}
            @endif

            @if($section === 'debates')
                <div class="card"><div class="card-head"><h3>{{ __('Débats') }}</h3></div>
                    @forelse($items as $d)
                        @php $mine = $d->participants->first(); @endphp
                        <div class="item"><x-icon name="debate"/><div class="item-body"><a href="{{ $d->url() }}" style="font-weight:700;color:var(--text)">{{ $d->title }}</a>
                            <div class="small muted">{{ $d->scheduled_at->translatedFormat('d M Y H:i') }} · {{ __($d->status) }}</div></div>
                            @if($mine?->status === 'invited')
                                <form method="POST" action="{{ route('debates.respond', $d) }}">@csrf<input type="hidden" name="answer" value="accept"><button class="btn btn-success btn-sm">{{ __('Accepter') }}</button></form>
                                <form method="POST" action="{{ route('debates.respond', $d) }}">@csrf<input type="hidden" name="answer" value="decline"><button class="btn btn-outline btn-sm">{{ __('Décliner') }}</button></form>
                            @else<span class="badge">{{ __($mine?->status ?? '') }}</span>@endif
                        </div>
                    @empty <div class="empty"><p>{{ __('Aucune invitation à un débat.') }}</p></div> @endforelse
                </div>{{ $items->links() }}
            @endif

            @if($section === 'notifications')
                <div class="card">@include('notifications.list', ['notifications' => $items])</div>{{ $items->links() }}
            @endif

            @if($section === 'profile')
                @if($profile)
                    <form method="POST" action="{{ route('candidate.profile.update') }}" enctype="multipart/form-data" class="card"><div class="card-head"><h3>{{ __('Gestion du profil candidat') }}</h3></div><div class="card-body">
                        @csrf @method('PUT')
                        <div class="alert alert-info"><x-icon name="history"/><div class="small">{{ __('Les modifications du nom, de l\'affiliation, du poste, de la circonscription, de la biographie et du parcours sont historisées publiquement.') }}</div></div>
                        <div class="row mb"><img src="{{ $profile->photoUrl() }}" alt="" class="avatar avatar-lg"><input type="file" name="photo" accept="image/*" class="input" aria-label="{{ __('Photo') }}"></div>
                        <div class="grid grid-2">
                            <div class="field"><label class="label" for="full_name">{{ __('Nom') }}</label><input id="full_name" name="full_name" class="input" value="{{ $profile->full_name }}" required></div>
                            <div class="field"><label class="label" for="party">{{ __('Affiliation politique déclarée') }}</label><input id="party" name="party" class="input" value="{{ $profile->party }}"></div>
                            <div class="field"><label class="label" for="position_sought">{{ __('Poste recherché') }}</label><select id="position_sought" name="position_sought" class="select">@foreach(config('vwajen.positions') as $k => $p)<option value="{{ $k }}" @selected($profile->position_sought === $k)>{{ __($p) }}</option>@endforeach</select></div>
                            <div class="field"><label class="label" for="constituency">{{ __('Circonscription') }}</label><input id="constituency" name="constituency" class="input" value="{{ $profile->constituency }}"></div>
                            <div class="field"><label class="label" for="department">{{ __('Département') }}</label><select id="department" name="department" class="select">@foreach(config('vwajen.departments') as $k => $d)<option value="{{ $k }}" @selected($profile->department === $k)>{{ $d['name'] }}</option>@endforeach</select></div>
                            <div class="field"><label class="label" for="city">{{ __('Ville') }}</label><input id="city" name="city" class="input" value="{{ $profile->city }}"></div>
                            <div class="field"><label class="label" for="election_year">{{ __('Année de l\'élection') }}</label><input id="election_year" type="number" name="election_year" class="input" value="{{ $profile->election_year }}"></div>
                            <div class="field"><label class="label" for="website">{{ __('Site web') }}</label><input id="website" type="url" name="website" class="input" value="{{ $profile->website }}"></div>
                        </div>
                        <div class="field"><label class="label" for="biography">{{ __('Biographie') }}</label><textarea id="biography" name="biography" class="textarea" required>{{ $profile->biography }}</textarea></div>
                        <div class="field"><label class="label" for="career">{{ __('Parcours') }}</label><textarea id="career" name="career" class="textarea" rows="6">{{ $profile->career }}</textarea></div>
                        <div class="field"><label class="label" for="change_note">{{ __('Motif de la modification (public)') }}</label><input id="change_note" name="change_note" class="input" maxlength="255"></div>
                        <button class="btn btn-primary">{{ __('Enregistrer') }}</button>
                    </div></form>
                @else
                    <div class="card empty"><p>{{ __('Aucun profil candidat.') }}</p><a href="{{ route('candidates.register') }}" class="btn btn-primary">{{ __('Inscription comme candidat') }}</a></div>
                @endif
                <a href="{{ route('settings.profile') }}" class="btn btn-outline">{{ __('Modifier le profil social (photo de couverture, bio…)') }}</a>
            @endif
        </div>
    </div>
@endsection
