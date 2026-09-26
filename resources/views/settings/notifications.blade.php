@extends('settings.layout')
@section('title', __('Notifications'))
@php
    $labels = ['follow' => __('Nouveaux abonnés'), 'like' => __('J\'aime'), 'comment' => __('Commentaires'), 'repost' => __('Reposts'), 'mention' => __('Mentions'),
        'question' => __('Nouvelles questions (candidats)'), 'candidate_answer' => __('Réponse d\'un candidat'), 'question_answered' => __('Réponse à une question soutenue'),
        'live' => __('Début d\'un live'), 'debate' => __('Nouveaux débats'), 'event' => __('Événements'), 'message' => __('Messages privés'),
        'system' => __('Notifications système'), 'moderation' => __('Notifications de modération')];
    $channels = ['database' => __('Dans l\'app'), 'mail' => __('E-mail'), 'push' => __('Push mobile'), 'sms' => 'SMS'];
@endphp
@section('settings')
    <form method="POST" action="{{ route('settings.notifications.update') }}" class="card"><div class="card-head"><h3>{{ __('Gestion des notifications') }}</h3>
        @if(config('vwajen.vapid.public'))<button type="button" class="btn btn-soft btn-sm" data-push-subscribe><x-icon name="phone"/>{{ __('Activer le push sur cet appareil') }}</button>@endif</div>
        @csrf @method('PUT')
        <div class="table-wrap"><table class="table">
            <thead><tr><th>{{ __('Type') }}</th>@foreach($channels as $c => $l)<th class="center">{{ $l }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach($types as $t)
                <tr><td>{{ $labels[$t] ?? $t }}</td>
                    @foreach($channels as $c => $l)
                        @php $forced = in_array($t, \App\Notifications\ActivityNotification::FORCED) && $c === 'database'; @endphp
                        <td class="center"><input type="checkbox" name="prefs[{{ $t }}][{{ $c }}]" value="1" @checked($forced || $user->wantsNotification($t, $c)) @disabled($forced || ($c === 'sms' && ! $user->phone_verified_at) || ($c === 'mail' && ! $user->email_verified_at)) aria-label="{{ ($labels[$t] ?? $t).' — '.$l }}" style="width:18px;height:18px;accent-color:var(--primary)">
                            @if($forced)<input type="hidden" name="prefs[{{ $t }}][{{ $c }}]" value="1">@endif</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div class="card-foot row between wrap"><span class="small faint">{{ __('Les notifications système et de modération restent toujours visibles dans l\'application. Les SMS nécessitent un téléphone vérifié.') }}</span><button class="btn btn-primary">{{ __('Enregistrer') }}</button></div>
    </form>
@endsection
