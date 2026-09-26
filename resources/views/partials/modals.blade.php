@auth
    {{-- Composer (publier une Vwa) --}}
    <div class="modal-backdrop" id="compose-modal" role="dialog" aria-modal="true" aria-labelledby="compose-title">
        <div class="modal">
            <div class="modal-head">
                <h2 id="compose-title">{{ __('Publier une Vwa') }}</h2>
                <button type="button" class="btn btn-ghost btn-icon" data-modal-close aria-label="{{ __('Fermer') }}"><x-icon name="x"/></button>
            </div>
            <div class="modal-body">@include('partials.composer', ['inModal' => true])</div>
        </div>
    </div>
@endauth

{{-- Partage : interne, autres applications, copie de lien --}}
<div class="modal-backdrop" id="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-title">
    <div class="modal">
        <div class="modal-head">
            <h2 id="share-title">{{ __('Partager') }}</h2>
            <button type="button" class="btn btn-ghost btn-icon" data-modal-close aria-label="{{ __('Fermer') }}"><x-icon name="x"/></button>
        </div>
        <div class="modal-body">
            <div class="input-group mb">
                <label for="share-url" class="sr-only">{{ __('Lien') }}</label>
                <input id="share-url" class="input" readonly>
                <button type="button" class="btn btn-primary" data-share-channel="link"><x-icon name="copy"/>{{ __('Copier') }}</button>
            </div>
            <div class="share-grid">
                <button type="button" class="share-btn" data-share-channel="native"><span class="circle" style="background:#475569"><x-icon name="share"/></span>{{ __('Autres apps') }}</button>
                <button type="button" class="share-btn" data-share-channel="whatsapp"><span class="circle" style="background:#25d366"><x-icon name="whatsapp"/></span>WhatsApp</button>
                <button type="button" class="share-btn" data-share-channel="facebook"><span class="circle" style="background:#1877f2"><x-icon name="facebook"/></span>Facebook</button>
                <button type="button" class="share-btn" data-share-channel="x"><span class="circle" style="background:#000"><x-icon name="x-logo"/></span>X</button>
                <button type="button" class="share-btn" data-share-channel="telegram"><span class="circle" style="background:#229ed9"><x-icon name="telegram"/></span>Telegram</button>
                <button type="button" class="share-btn" data-share-channel="email"><span class="circle" style="background:#ea580c"><x-icon name="mail"/></span>E-mail</button>
                @auth
                    <button type="button" class="share-btn" data-share-channel="repost"><span class="circle" style="background:var(--repost)"><x-icon name="repost"/></span>{{ __('Repost') }}</button>
                    <button type="button" class="share-btn" data-share-channel="quote"><span class="circle" style="background:var(--primary)"><x-icon name="edit"/></span>{{ __('Citer') }}</button>
                @endauth
            </div>
            @auth
                @php
                    $recentConversations = \App\Models\Conversation::whereHas('participants', fn ($q) => $q->where('user_id', auth()->id())->whereNull('left_at'))
                        ->with('users')->orderByDesc('last_message_at')->limit(8)->get();
                @endphp
                @if($recentConversations->isNotEmpty())
                    <form method="POST" class="mt" data-share-internal data-ajax>
                        @csrf
                        <label class="label" for="share-conv">{{ __('Envoyer par message privé') }}</label>
                        <div class="row">
                            <select id="share-conv" name="conversation_id" class="select grow">
                                @foreach($recentConversations as $c)
                                    <option value="{{ $c->id }}">{{ $c->titleFor(auth()->user()) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-soft"><x-icon name="send"/>{{ __('Envoyer') }}</button>
                        </div>
                        <input type="text" name="body" class="input mt-sm" placeholder="{{ __('Ajouter un message (facultatif)') }}" maxlength="500">
                    </form>
                @endif
            @endauth
        </div>
    </div>
</div>

@auth
    {{-- Citation (quote) --}}
    <div class="modal-backdrop" id="quote-modal" role="dialog" aria-modal="true" aria-labelledby="quote-title">
        <div class="modal">
            <div class="modal-head">
                <h2 id="quote-title">{{ __('Citer cette Vwa') }}</h2>
                <button type="button" class="btn btn-ghost btn-icon" data-modal-close aria-label="{{ __('Fermer') }}"><x-icon name="x"/></button>
            </div>
            <form method="POST" action="{{ route('posts.store') }}" class="modal-body">
                @csrf
                <input type="hidden" name="quote_of_id" id="quote-of-id">
                <textarea name="body" class="textarea" maxlength="{{ config('vwajen.limits.post_length') }}" placeholder="{{ __('Ajoutez votre commentaire…') }}" aria-label="{{ __('Votre commentaire') }}"></textarea>
                <div class="quote mt-sm" id="quote-preview"></div>
                <div class="row mt-sm" style="justify-content:flex-end"><button class="btn btn-primary">{{ __('Publier') }}</button></div>
            </form>
        </div>
    </div>
@endauth
