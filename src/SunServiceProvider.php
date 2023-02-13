<?php

namespace SunAsterisk\Auth;

use Illuminate\Contracts\Container\Container;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Manager\Contracts\Helpers\ConfigRetrieverInterface;
use SocialiteProviders\Manager\Helpers\ConfigRetriever;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\SocialiteManager;

final class SunServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function boot(): void
    {
        // parent::boot();

        $this->publishes([
            __DIR__.'/../config/sun-asterisk.php' => $this->app->configPath('sun-asterisk.php'),
        ], 'sun-asterisk');

        $this->extendAuthGuard();
        $this->socialiteServiceBoot();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sun-asterisk.php', 'sun-asterisk');

        $this->app->singleton(
            Contracts\AuthJWTInterface::class,
            static fn (Container $app) => $app->make(SunProjectManager::class)->authJWT(),
        );

        $this->app->singleton(
            Contracts\AuthSocialInterface::class,
            static fn (Container $app) => $app->make(SunProjectManager::class)->authSocial(),
        );

        $this->app->singleton(Factory::class, function ($app) {
            return new SocialiteManager($app);
        });

        if (! $this->app->bound(ConfigRetrieverInterface::class)) {
            $this->app->singleton(ConfigRetrieverInterface::class, function () {
                return new ConfigRetriever();
            });
        }
    }

    /**
     * Extend Laravel's Auth.
     *
     * @return void
     */
    protected function extendAuthGuard()
    {
        $this->app['auth']->extend('sun', function ($app, $name, array $config) {
            $jwt = new SunJwt($this->app->config->get('sun-asterisk')['auth']);
            $guard = new SunGuard(
                $jwt,
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );
            app()->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    protected function socialiteServiceBoot()
    {
        if ($this->app instanceof \Illuminate\Foundation\Application) {
            // Laravel
            $this->app->booted(function () {
                $socialiteWasCalled = app(SocialiteWasCalled::class);

                event($socialiteWasCalled);
            });
        } else {
            // Lumen
            $socialiteWasCalled = app(SocialiteWasCalled::class);

            event($socialiteWasCalled);
        }
    }
}
