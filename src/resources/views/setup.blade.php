@extends(\Illuminate\Support\Facades\Config::get(\MHMartinez\TwoFactorAuth\Services\TwoFactorAuthService::CONFIG_KEY . '.layout'))

@section('twoFactorAuthSetup')
    <p class="text-center">{{ \Illuminate\Support\Facades\Config::get(\MHMartinez\TwoFactorAuth\Services\TwoFactorAuthService::CONFIG_KEY . '.texts.setup_description') }} <strong>{{ $secret }}</strong></p>
    <div class="text-center mb-3">
        @if(\Illuminate\Support\Str::startsWith($QR_Image, 'data:image'))
            <img src="{{ $QR_Image }}" alt="QR">
        @else
            {!! $QR_Image !!}
        @endif
    </div>
@endsection

@section('content')
    @include(\MHMartinez\TwoFactorAuth\Services\TwoFactorAuthService::CONFIG_KEY . '::form', ['formTitle' => \Illuminate\Support\Facades\Config::get(\MHMartinez\TwoFactorAuth\Services\TwoFactorAuthService::CONFIG_KEY . '.texts.setup_title') ])
@endsection