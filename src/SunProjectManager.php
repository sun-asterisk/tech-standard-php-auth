<?php

namespace SunAsterisk\Auth;

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
        $cacheProvider = $this->app->make(Providers\Storage::class);
        $factory = (new Factory)
            ->withConfig($config)
            ->withBlacklist($cacheProvider);

        return $factory->createAuthJWT();
    }

    public function authSocial(): Contracts\AuthSocialInterface
    {
        $factory = new Factory();

        return $factory->createAuthSocial();
    }
}
