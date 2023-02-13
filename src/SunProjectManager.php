<?php

namespace SunAsterisk\Auth;

use SunAsterisk\Auth\Contracts;
use Illuminate\Contracts\Container\Container;

class SunProjectManager
{
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function authJWT(): Contracts\AuthJWTInterface
    {
        $config = $this->app->config->get('sun-asterisk');
        $factory = (new Factory)->withConfig($config);

        return $factory->createAuthJWT();
    }

    public function authSocial(): Contracts\AuthSocialInterface
    {
        $factory = new Factory();

        return $factory->createAuthSocial();
    }
}
