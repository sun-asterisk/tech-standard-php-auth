<?php

namespace SunAsterisk\Auth\Services;

use Illuminate\Http\RedirectResponse;
use SunAsterisk\Auth\Contracts\AuthSocialInterface;
use Laravel\Socialite\Facades\Socialite;
use InvalidArgumentException;
use stdClass;

final class AuthSocialService implements AuthSocialInterface
{
    public function __construct()
    {
        //
    }

    /**
     * [socialSignIn]
     * @param  string $provider [The Provider should received from https://socialiteproviders.com/about/]
     * @return [Illuminate\Http\RedirectResponse]
     */
    public function socialSignIn(?string $provider): RedirectResponse
    {
        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            throw new InvalidArgumentException('provider is invalid!');
        }
    }

    /**
     * [socialCallback]
     * @param  string $provider [The Provider should received from https://socialiteproviders.com/about/]
     * @return [stdClass]
     */
    public function socialCallback(?string $provider): stdClass
    {
        try {
            return Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            throw new InvalidArgumentException('provider is invalid!');
        }
    }
}
