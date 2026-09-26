@if(session('status'))
    <div class="alert alert-success" role="status"><x-icon name="check-circle"/><div>{{ session('status') }}</div></div>
@endif
@if(session('warning'))
    <div class="alert alert-warning" role="alert"><x-icon name="alert"/><div>{{ session('warning') }}
        @if(auth()->user()?->deletion_requested_at)
            <form method="POST" action="{{ route('settings.account.cancel-deletion') }}" class="mt-sm">@csrf
                <button class="btn btn-sm btn-outline">{{ __('Annuler la suppression') }}</button></form>
        @endif
    </div></div>
@endif
@if($errors->any() && ! ($hideErrors ?? false))
    <div class="alert alert-danger" role="alert"><x-icon name="alert"/>
        <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
    </div>
@endif
@auth
    @if(! auth()->user()->hasVerifiedEmail() && ! request()->routeIs('verification.*'))
        <div class="alert alert-info"><x-icon name="mail"/>
            <div>{{ __('Vérifiez votre adresse e-mail pour publier et interagir.') }}
                <a href="{{ route('verification.notice') }}">{{ __('Renvoyer le lien') }}</a></div>
        </div>
    @endif
@endauth
@if($banner = setting('maintenance_banner'))
    <div class="alert alert-warning"><x-icon name="info"/><div>{{ $banner }}</div></div>
@endif
