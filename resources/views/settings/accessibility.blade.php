@extends('settings.layout')
@section('title', __('Affichage et accessibilité'))
@section('settings')
    <form method="POST" action="{{ route('settings.accessibility.update') }}" class="card"><div class="card-body">
        @csrf @method('PUT')
        <h3>{{ __('Thème') }}</h3>
        <div class="pills mb">
            @foreach(['system' => __('Automatique (système)'), 'light' => '☀️ '.__('Mode clair'), 'dark' => '🌙 '.__('Mode sombre')] as $k => $l)
                <label><input type="radio" name="theme" value="{{ $k }}" class="pill-check" @checked($user->theme === $k)><span class="pill">{{ $l }}</span></label>
            @endforeach
        </div>
        <h3>{{ __('Taille du texte') }}</h3>
        <div class="pills mb">
            @foreach(['sm' => 'A', 'md' => 'A', 'lg' => 'A', 'xl' => 'A'] as $k => $l)
                <label><input type="radio" name="font_size" value="{{ $k }}" class="pill-check" @checked($user->font_size === $k)><span class="pill" style="font-size:{{ ['sm' => '.8rem', 'md' => '.95rem', 'lg' => '1.1rem', 'xl' => '1.3rem'][$k] }}">{{ $l }} <span class="sr-only">{{ ['sm' => __('Petit'), 'md' => __('Normal'), 'lg' => __('Grand'), 'xl' => __('Très grand')][$k] }}</span></span></label>
            @endforeach
        </div>
        <label class="switch mb"><input type="checkbox" name="high_contrast" value="1" @checked($user->high_contrast)><span class="track"></span><span><strong>{{ __('Contraste élevé') }}</strong><br><span class="small muted">{{ __('Améliore la lisibilité des textes et contours.') }}</span></span></label><br>
        <label class="switch mb"><input type="checkbox" name="reduce_motion" value="1" @checked($user->reduce_motion)><span class="track"></span>{{ __('Réduire les animations') }}</label>
        <hr>
        <h3>{{ __('Faible connexion') }}</h3>
        <label class="switch mb"><input type="checkbox" name="data_saver" value="1" @checked($user->data_saver)><span class="track"></span><span><strong>{{ __('Mode économie de données') }}</strong><br><span class="small muted">{{ __('Vidéos en basse qualité, pas de préchargement, aperçus de liens désactivés, actualisations moins fréquentes.') }}</span></span></label><br>
        <label class="switch mb"><input type="checkbox" name="reduce_autoplay" value="1" @checked($user->reduce_autoplay)><span class="track"></span>{{ __('Désactiver la lecture automatique des vidéos') }}</label>
        <hr>
        <p class="small muted"><x-icon name="accessibility" style="vertical-align:-4px"/> {{ __('Navigation au clavier : Tab / Maj+Tab, Échap pour fermer, « n » pour publier, « / » pour rechercher. Les images acceptent un texte alternatif et les vidéos des sous-titres.') }}
            <a href="{{ route('pages.show', 'accessibility') }}">{{ __('Déclaration d\'accessibilité') }}</a></p>
        <button class="btn btn-primary">{{ __('Enregistrer') }}</button>
    </div></form>
@endsection
