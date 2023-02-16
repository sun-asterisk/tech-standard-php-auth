<?php

namespace SunAsterisk\Auth\Contracts;

use Illuminate\Http\RedirectResponse;
use stdClass;

interface AuthSocialInterface
{
    /**
     * [socialSignIn]
     * @param  string $provider [The Provider should received from https://socialiteproviders.com/about/]
     * @return [Illuminate\Http\RedirectResponse]
     */
    public function socialSignIn(?string $provider): RedirectResponse;

    /**
     * [socialCallback]
     * @param  string $provider [The Provider should received from https://socialiteproviders.com/about/]
     * @return [stdClass]
     */
    public function socialCallback(?string $provider): stdClass;
}
