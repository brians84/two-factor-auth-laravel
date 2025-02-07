<?php

namespace MHMartinez\TwoFactorAuth\app\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use JetBrains\PhpStorm\NoReturn;
use MHMartinez\TwoFactorAuth\TwoFactorAuth;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FALaravel\Google2FA;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;

class TwoFactorAuthController extends Controller
{
    public function __construct(private Google2FA $google2FA, private TwoFactorAuth $twoFactorAuth)
    {
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException|MissingQrCodeServiceException
     * @throws InvalidCharactersException|SecretKeyTooShortException
     */
    #[NoReturn] public function setupTwoFactorAuth(): Factory|View|Application|RedirectResponse
    {
        if (!Auth::guard(config('two_factor_auth.guard'))->user()) {
            return Redirect::to(url('/'));
        }

        $userSecret = $this->twoFactorAuth->generateUserSecretKey();
        $QR_Image = $this->twoFactorAuth->generateQR($userSecret);

        return view('two_factor_auth::setup', ['QR_Image' => $QR_Image, 'secret' => $userSecret]);
    }

    #[NoReturn] public function validateTwoFactorAuth(): Factory|View|Application|RedirectResponse
    {
        if (!Auth::guard(config('two_factor_auth.guard'))->user()) {
            return Redirect::to(url('/'));
        }

        return view('two_factor_auth::validate', ['secret' => $this->twoFactorAuth->getUserSecretKey()]);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException|SecretKeyTooShortException|InvalidCharactersException
     */
    #[NoReturn] public function authenticateTwoFactorAuth(): RedirectResponse
    {
        if (!Auth::guard(config('two_factor_auth.guard'))->user()) {
            return Redirect::to(url('/'));
        }

        $oneTimePass = $this->twoFactorAuth->getOneTimePasswordRequestField();
        $userSecret = $this->twoFactorAuth->getUserSecretKey();

        if (!$oneTimePass || !$userSecret || !$this->google2FA->verifyKey($userSecret, $oneTimePass)) {
            Session::put(config('two_factor_auth.user_secret_key'), $userSecret);

            return Redirect::back()->withErrors(['error' => __('two_factor_auth::form.error_msg')]);
        }
        $this->twoFactorAuth->updateOrCreateUserSecret($userSecret, date('Y-m-d H:i:s'));
        $this->twoFactorAuth->handleRemember();
        $this->google2FA->login();

        return Redirect::route(config('two_factor_auth.route_after_validation'));

    }
}
