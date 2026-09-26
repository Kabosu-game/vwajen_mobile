@extends('layouts.admin')
@section('title', __('Paramètres de la plateforme'))
@php
    $groups = ['general' => __('Général'), 'security' => __('Sécurité et limites'), 'moderation' => __('Modération'), 'features' => __('Fonctionnalités')];
    $labels = ['site_tagline' => __('Slogan'), 'registration_open' => __('Inscriptions ouvertes'), 'maintenance_banner' => __('Bannière d\'information (vide = aucune)'),
        'election_date' => __('Date des prochaines élections (AAAA-MM-JJ)'), 'require_email_verification' => __('E-mail vérifié requis pour publier'),
        'limit_register_per_hour' => __('Inscriptions max par IP / heure'), 'limit_posts_per_minute' => __('Publications max / minute'), 'limit_posts_per_hour' => __('Publications max / heure'),
        'limit_comments_per_minute' => __('Commentaires max / minute'), 'limit_messages_per_minute' => __('Messages max / minute'),
        'banned_words' => __('Mots interdits (séparés par des virgules)'), 'auto_hide_threshold' => __('Masquage automatique après N signalements (0 = désactivé)'),
        'lives_enabled' => __('Lives activés'), 'shorts_enabled' => __('Shorts activés'), 'ai_features_enabled' => __('Fonctions IA activées'), 'contact_email' => __('E-mail de contact')];
@endphp
@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')
        @foreach($groups as $g => $title)
            @isset($values[$g])
                <div class="card"><div class="card-head"><h3>{{ $title }}</h3></div><div class="card-body">
                    @foreach($values[$g] as $key => $s)
                        @if($s['type'] === 'bool')
                            <label class="switch mb" style="display:flex"><input type="checkbox" name="{{ $key }}" value="1" @checked($s['value'])><span class="track"></span>{{ $labels[$key] ?? $key }}</label>
                        @elseif($key === 'banned_words')
                            <div class="field"><label class="label" for="{{ $key }}">{{ $labels[$key] }}</label><textarea id="{{ $key }}" name="{{ $key }}" class="textarea" rows="2">{{ $s['value'] }}</textarea></div>
                        @else
                            <div class="field"><label class="label" for="{{ $key }}">{{ $labels[$key] ?? $key }}</label><input id="{{ $key }}" name="{{ $key }}" class="input" type="{{ $s['type'] === 'int' ? 'number' : 'text' }}" value="{{ $s['value'] }}"></div>
                        @endif
                    @endforeach
                </div></div>
            @endisset
        @endforeach
        <button class="btn btn-primary">{{ __('Enregistrer les paramètres') }}</button>
    </form>
    <div class="card mt"><div class="card-head"><h3>{{ __('Configuration serveur (.env)') }}</h3></div><div class="card-body small muted">
        {{ __('Google/Apple, SMS (Twilio), Web Push (VAPID), ffmpeg, serveurs TURN, clé IA et transcription se configurent dans le fichier .env. Voir le Monitoring pour l\'état de chaque service.') }}
    </div></div>
@endsection
