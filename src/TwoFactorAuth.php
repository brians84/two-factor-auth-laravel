<?php

namespace MHMartinez\TwoFactorAuth;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use MHMartinez\TwoFactorAuth\app\Models\TwoFactorAuth as TwoFactorAuthModel;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FALaravel\Google2FA;
use PragmaRX\Google2FAQRCode\Exceptions\MissingQrCodeServiceException;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;

class TwoFactorAuth
{
    /**
     * @throws IncompatibleWithGoogleAuthenticatorException|SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function generateUserSecretKey(): string
    {
        if (Session::has(config('two_factor_auth.user_secret_key'))) {
            return Session::get(config('two_factor_auth.user_secret_key'));
        }

        $userSecret = app(Google2FA::class)->generateSecretKey();
        $this->updateOrCreateUserSecret($userSecret);

        return $userSecret;
    }

    /**
     * @throws MissingQrCodeServiceException
     */
    public function generateQR(string $userSecret): string
    {
        $google2FA = app(Google2FA::class);
        $google2FA->setQrcodeService(new Bacon(new SvgImageBackEnd()));

        return $google2FA->getQRCodeInline(
            config('app.name'),
            Auth::guard(config('two_factor_auth.guard'))->user()->getAttribute('email'),
            $userSecret,
        );
    }

    public function getUserSecretKey(): ?string
    {
        /** @var TwoFactorAuthModel $secret */
        $secret = $this->getUserTwoFactorAuthSecret(Auth::guard(config('two_factor_auth.guard'))->user());

        return $secret ? decrypt($secret->secret) : null;
    }

    public function getOneTimePasswordRequestField(): ?string
    {
        $inputKey = config('two_factor_auth.otp_input');

        return Request::has($inputKey)
            ? Request::get($inputKey)
            : null;
    }

    public function handleRemember(): void
    {
        if (Session::has(config('two_factor_auth.remember_key'))) {
            $days = config('two_factor_auth.2fa_expires');
            $key = config('two_factor_auth.remember_key');
            $minutes = $days === 0 ? null : $days * 60 * 24;

            Cookie::queue(Cookie::make($key, true, $minutes));
            Session::remove(config('two_factor_auth.remember_key'));
        }

        Session::remove(config('two_factor_auth.user_secret_key'));
    }

    public function getUserTwoFactorAuthConfirmed(?Authenticatable $user): Builder|Model|null
    {
        return !$user
            ? null
            : TwoFactorAuthModel::query()
                ->where('user_id', $user->id)
                ->whereNotNull('setup_confirmed_at')
                ->first();
    }


    public function getUserTwoFactorAuthSecret(?Authenticatable $user): Builder|Model|null
    {
        return !$user
            ? null
            : TwoFactorAuthModel::query()
                ->where('user_id', $user->id)
                ->first();
    }

    public function updateOrCreateUserSecret(string $userSecret, $setup_confirmed_at = NULL)
    {
        TwoFactorAuthModel::updateOrCreate(
            ['user_id' => Auth::guard(config('two_factor_auth.guard'))->user()->id],
            ['secret' => $userSecret, 'setup_confirmed_at' => $setup_confirmed_at]
        );
    }
}