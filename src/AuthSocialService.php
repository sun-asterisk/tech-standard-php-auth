<?php

namespace SunAsterisk\Auth;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use InvalidArgumentException;
use stdClass;

final class AuthSocialService implements Contracts\AuthSocialInterface
{
    public function __construct()
    {
        //
    }

    public function socialSignIn(?string $provider): RedirectResponse
    {
        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Exception $e) {
            throw new InvalidArgumentException('provider is invalid!');
        }
    }

    public function socialCallback(?string $provider): stdClass
    {
        try {
            return Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            throw new InvalidArgumentException('provider is invalid!');
        }
    }
}
