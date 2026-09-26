@php
    /** Histogramme léger (sans JavaScript) : $series = [date|label => valeur] */
    $values = collect($series);
    $max = max(1, (int) $values->max());
    $color = $color ?? 'var(--primary)';
    $h = $height ?? 140;
@endphp
<figure style="margin:0" aria-label="{{ $label ?? '' }}">
    <div style="display:flex;align-items:flex-end;gap:2px;height:{{ $h }}px;border-bottom:1px solid var(--border)" role="img" aria-label="{{ $label ?? '' }} : {{ $values->sum() }}">
        @foreach($values as $k => $v)
            <div title="{{ $k }} : {{ $v }}" style="flex:1;min-width:2px;background:{{ $color }};opacity:.85;border-radius:3px 3px 0 0;height:{{ max(2, round($v * 100 / $max)) }}%"></div>
        @endforeach
    </div>
    <figcaption class="row between small faint mt-sm"><span>{{ $values->keys()->first() }}</span><span>{{ __('Total') }} : <b>{{ number_format($values->sum(), 0, ',', ' ') }}</b> · {{ __('max') }} {{ $max }}</span><span>{{ $values->keys()->last() }}</span></figcaption>
</figure>
