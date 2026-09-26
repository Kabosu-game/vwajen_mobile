@extends('layouts.app')
@section('title', $election->name)
@section('content')
    <div class="page-head"><a href="{{ route('elections.index') }}" class="back" aria-label="{{ __('Retour') }}"><x-icon name="arrow-left"/></a><h1 class="truncate">{{ $election->name }}</h1></div>
    <div class="card"><div class="card-body">
        <dl class="kv"><dt>{{ __('Date') }}</dt><dd>{{ $election->held_on->translatedFormat('l d F Y') }}</dd><dt>{{ __('Tour') }}</dt><dd>{{ $election->round }}</dd>
            <dt>{{ __('Source') }}</dt><dd>@if($election->source_url)<a href="{{ $election->source_url }}" target="_blank" rel="noopener">{{ $election->source_name ?: $election->source_url }}</a>@else{{ $election->source_name ?: '—' }}@endif</dd></dl>
        @if($election->description)<p class="mt">{{ $election->description }}</p>@endif
    </div></div>
    @if(! $election->results_published)
        <div class="card empty"><x-icon name="clock"/><p>{{ __('Les résultats officiels ne sont pas encore publiés.') }}</p></div>
    @else
        @foreach($results as $constituency => $rows)
            @php $total = $rows->sum('votes'); @endphp
            <div class="card"><div class="card-head"><h3>{{ $constituency }}</h3><span class="small faint">{{ number_format($total, 0, ',', ' ') }} {{ __('voix') }}</span></div><div class="card-body">
                @foreach($rows as $r)
                    <div class="mb" style="margin-bottom:.7rem">
                        <div class="row between"><span>@if($r->user)<a href="{{ $r->user->profileUrl() }}"><strong>{{ $r->candidate_name }}</strong></a>@else<strong>{{ $r->candidate_name }}</strong>@endif
                            @if($r->party)<span class="faint small">· {{ $r->party }}</span>@endif @if($r->elected)<span class="badge badge-success">{{ __('Élu') }}</span>@endif</span>
                            <span class="small">{{ number_format($r->votes, 0, ',', ' ') }} ({{ $r->percentage ?? ($total ? round($r->votes * 100 / $total, 2) : 0) }} %)</span></div>
                        <div class="progress mt-sm"><span style="width:{{ $r->percentage ?? ($total ? $r->votes * 100 / $total : 0) }}%"></span></div>
                    </div>
                @endforeach
            </div></div>
        @endforeach
        <p class="small faint">{{ __('Présentation par ordre de voix, telle que publiée par la source officielle.') }}</p>
    @endif
@endsection
