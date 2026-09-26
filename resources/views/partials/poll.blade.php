@php
    $viewer = auth()->user();
    $voted = $poll->votedOptionIds($viewer);
    $showResults = $voted || ! $poll->isOpen() || ($viewer && $viewer->id === ($post->user_id ?? $poll->post?->user_id));
    $total = $poll->options->sum('votes_count');
@endphp
<form method="POST" action="{{ route('polls.vote', $poll) }}" class="poll {{ $showResults ? 'closed' : '' }}" data-poll-form>
    @csrf
    @foreach($poll->options as $opt)
        @if($showResults)
            <div class="poll-option {{ in_array($opt->id, $voted) ? 'mine' : '' }}">
                <span class="bar" style="width:{{ $poll->percent($opt) }}%"></span>
                <span class="label-row"><span>{{ $opt->label }} @if(in_array($opt->id, $voted))<x-icon name="check-circle" style="width:15px;height:15px;vertical-align:-2px"/>@endif</span><span>{{ $poll->percent($opt) }}%</span></span>
            </div>
        @elseif($poll->multiple)
            <label class="poll-option"><span class="label-row"><span><input type="checkbox" name="options[]" value="{{ $opt->id }}"> {{ $opt->label }}</span></span></label>
        @else
            <button type="submit" name="options[]" value="{{ $opt->id }}" class="poll-option" @guest data-auth @endguest><span class="label-row"><span>{{ $opt->label }}</span></span></button>
        @endif
    @endforeach
    @if(! $showResults && $poll->multiple)<button class="btn btn-soft btn-sm" @guest data-auth @endguest>{{ __('Voter') }}</button>@endif
    <div class="small faint">{{ trans_choice(':n vote|:n votes', $total, ['n' => $total]) }} ·
        {{ $poll->isOpen() ? ($poll->ends_at ? __('Se termine :when', ['when' => $poll->ends_at->diffForHumans()]) : '') : __('Sondage terminé') }}</div>
</form>
