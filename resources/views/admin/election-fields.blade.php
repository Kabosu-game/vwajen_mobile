<div class="grid grid-3">
    <input name="name" class="input" placeholder="{{ __('Nom') }}" required value="{{ $e?->name }}" aria-label="{{ __('Nom') }}">
    <select name="type" class="select" aria-label="{{ __('Type') }}">@foreach(['presidential' => __('Présidentielle'), 'legislative' => __('Législatives'), 'municipal' => __('Municipales'), 'local' => __('Locales'), 'referendum' => __('Référendum')] as $k => $l)<option value="{{ $k }}" @selected($e?->type === $k)>{{ $l }}</option>@endforeach</select>
    <input type="date" name="held_on" class="input" required value="{{ $e?->held_on?->format('Y-m-d') }}" aria-label="{{ __('Date') }}">
    <input type="number" name="round" class="input" min="1" max="3" value="{{ $e?->round ?? 1 }}" aria-label="{{ __('Tour') }}">
    <input name="source_name" class="input" placeholder="{{ __('Source') }}" value="{{ $e?->source_name }}" aria-label="{{ __('Source') }}">
    <input name="source_url" type="url" class="input" placeholder="https://" value="{{ $e?->source_url }}" aria-label="{{ __('Lien source') }}">
</div>
<textarea name="description" class="textarea mt-sm" rows="2" placeholder="{{ __('Description') }}" aria-label="{{ __('Description') }}">{{ $e?->description }}</textarea>
<label class="check mt-sm"><input type="checkbox" name="results_published" value="1" @checked($e?->results_published)>{{ __('Résultats publiés') }}</label>
