<?php

namespace SunAsterisk\Auth\Contracts;

use Illuminate\Http\RedirectResponse;
use stdClass;

interface AuthSocialInterface
{
    /**
     * [socialSignIn]
     * @param  string $provider
     * @return [Illuminate\Http\RedirectResponse]
     */
    public function socialSignIn(?string $provider): RedirectResponse;

    /**
     * [socialCallback]
     * @param  string $provider
     * @return [stdClass]
     */
    public function socialCallback(?string $provider): stdClass;
}
