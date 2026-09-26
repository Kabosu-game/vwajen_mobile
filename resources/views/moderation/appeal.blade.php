@extends(auth()->user()->status === 'active' ? 'layouts.app' : 'layouts.guest')
@section('title', __('Faire appel'))
@section('content')
    <div class="card"><div class="card-body">
        <h1 style="font-size:1.3rem">{{ __('Faire appel d\'une sanction') }}</h1>
        <div class="quote mb"><strong>{{ $sanction->typeLabel() }}</strong> — {{ $sanction->created_at->translatedFormat('d M Y') }}<div class="small">{{ $sanction->reason }}</div></div>
        <form method="POST" action="{{ route('appeals.store', $sanction) }}">@csrf
            <div class="field"><label class="label" for="body">{{ __('Expliquez pourquoi cette décision devrait être revue') }}</label>
                <textarea id="body" name="body" class="textarea" rows="6" required minlength="20" maxlength="3000">{{ old('body') }}</textarea>
                @error('body')<div class="error">{{ $message }}</div>@enderror</div>
            <p class="small muted">{{ __('Maximum 2 appels par sanction. Réponse généralement sous 72 heures.') }}</p>
            <button class="btn btn-primary">{{ __('Envoyer l\'appel') }}</button>
        </form>
    </div></div>
@endsection
