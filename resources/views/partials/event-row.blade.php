<a href="{{ $e->url() }}" class="item" style="color:var(--text)" data-item="event:{{ $e->id }}">
    <div class="date-box"><div class="m">{{ $e->localStart()->translatedFormat('M') }}</div><div class="d">{{ $e->localStart()->format('d') }}</div></div>
    <div class="item-body">
        <div style="font-weight:700">{{ $e->title }}</div>
        <div class="small muted">
            <x-icon name="clock" style="width:14px;height:14px;vertical-align:-2px"/> {{ $e->localStart()->translatedFormat('l H:i') }} ({{ $e->timezone }})
            · @if($e->is_online)<x-icon name="globe" style="width:14px;height:14px;vertical-align:-2px"/> {{ __('En ligne') }}@else<x-icon name="pin" style="width:14px;height:14px;vertical-align:-2px"/> {{ trim(($e->location_name ? $e->location_name.', ' : '').$e->city) ?: country_name($e->country) }}@endif
        </div>
        <div class="small faint mt-sm">{{ trans_choice(':n participant|:n participants', $e->going_count, ['n' => $e->going_count]) }} · {{ trans_choice(':n intéressé|:n intéressés', $e->interested_count, ['n' => $e->interested_count]) }}</div>
    </div>
    @if($e->isPast())<span class="badge">{{ __('Passé') }}</span>@endif
</a>
