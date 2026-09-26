@extends('settings.layout')
@section('title', __('Sanctions et appels'))
@section('settings')
    <div class="card"><div class="card-head"><h3>{{ __('Historique des sanctions') }}</h3></div>
        @forelse($sanctions as $s)
            <div class="item"><div class="item-body">
                <div class="row wrap"><strong>{{ $s->typeLabel() }}</strong>
                    @if($s->revoked_at)<span class="badge badge-success">{{ __('Levée') }}</span>@elseif($s->isActive())<span class="badge badge-danger">{{ __('Active') }}</span>@else<span class="badge">{{ __('Expirée') }}</span>@endif</div>
                <div class="small">{{ $s->reason }}</div>
                <div class="small faint">{{ $s->created_at->translatedFormat('d M Y') }}@if($s->expires_at) → {{ $s->expires_at->translatedFormat('d M Y') }}@endif</div>
                @foreach($s->appeals as $a)
                    <div class="quote small mt-sm"><strong>{{ __('Appel') }}</strong> — {{ ['pending' => __('En cours d\'examen'), 'accepted' => __('Accepté'), 'rejected' => __('Rejeté')][$a->status] }}<div>{{ $a->body }}</div>@if($a->response)<div class="mt-sm"><em>{{ __('Réponse') }} :</em> {{ $a->response }}</div>@endif</div>
                @endforeach
            </div>
                @if(! $s->revoked_at && ! $s->appeals->where('status', 'pending')->count() && $s->appeals->count() < 2)
                    <a href="{{ route('appeals.create', $s) }}" class="btn btn-outline btn-sm">{{ __('Faire appel') }}</a>
                @endif
            </div>
        @empty <div class="empty"><x-icon name="check-circle"/><p>{{ __('Aucune sanction. Merci de contribuer à une communauté respectueuse !') }}</p></div> @endforelse
    </div>
@endsection
