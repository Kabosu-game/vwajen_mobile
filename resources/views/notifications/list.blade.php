@php
    $icons = ['follow' => 'user-plus', 'like' => 'heart', 'comment' => 'comment', 'repost' => 'repost', 'mention' => 'at', 'question' => 'question',
        'candidate_answer' => 'check-circle', 'question_answered' => 'check-circle', 'live' => 'live', 'debate' => 'debate', 'event' => 'calendar',
        'system' => 'info', 'moderation' => 'shield', 'message' => 'mail'];
@endphp
@forelse($notifications as $n)
    @php $data = $n->data; $type = $data['type'] ?? 'system'; @endphp
    <a href="{{ route('notifications.read', $n->id) }}" class="notif {{ $n->read_at ? '' : 'unread' }}"
       onclick="event.preventDefault(); fetch(this.href, {method:'POST', headers:{'X-CSRF-TOKEN': Vwajen.csrf, 'Accept':'application/json'}}).finally(() => location.href = @js($data['url'] ?? route('notifications.index')));">
        <span class="type-icon {{ $type }}"><x-icon name="{{ $icons[$type] ?? 'bell' }}"/></span>
        @if(! empty($data['actor_avatar']))<img src="{{ $data['actor_avatar'] }}" alt="" class="avatar avatar-sm">@endif
        <div class="grow">
            <div>{{ \App\Notifications\ActivityNotification::render($data['text_key'] ?? '', $data['params'] ?? []) }}</div>
            <div class="small faint">{{ $n->created_at->diffForHumans() }}</div>
        </div>
        @unless($n->read_at)<span class="dot" aria-label="{{ __('Non lue') }}"></span>@endunless
    </a>
@empty
    <div class="empty"><x-icon name="bell"/><h3>{{ __('Aucune notification') }}</h3><p>{{ __('Les interactions avec vos contenus apparaîtront ici.') }}</p></div>
@endforelse
