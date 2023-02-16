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
        $this->publishes([
            __DIR__.'/../config/sun-asterisk.php' => $this->app->configPath('sun-asterisk.php'),
        ], 'sun-asterisk');

        $this->extendAuthGuard();
        if ($this->app->config->get('sun-asterisk.auth.enabled_social')) {
            $this->socialiteServiceBoot();
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sun-asterisk.php', 'sun-asterisk');

        $this->registerAuthJWT();

        if ($this->app->config->get('sun-asterisk.auth.enabled_social')) {
            $this->registerAuthSocial();
            $this->registerSocialiteProviders();
        }
    }

    protected function registerAuthJWT(): void
    {
        $this->app->singleton(
            Contracts\AuthJWTInterface::class,
            static fn (Container $app) => $app->make(SunProjectManager::class)->authJWT(),
        );
    }

    protected function registerAuthSocial(): void
    {
        $this->app->singleton(
            Contracts\AuthSocialInterface::class,
            static fn (Container $app) => $app->make(SunProjectManager::class)->authSocial(),
        );
    }

    protected function registerSocialiteProviders(): void
    {
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
    protected function extendAuthGuard(): void
    {
        $this->app['auth']->extend('sun', function ($app, $name, array $config) {
            $blackList = new SunBlacklist($app->make(Providers\Storage::class));
            $jwt = new SunJWT($blackList, $app->config->get('sun-asterisk.auth'));

            $guard = new SunGuard(
                $jwt,
                $app['auth']->createUserProvider($config['provider']),
                $app['request']
            );
            app()->refresh('request', $guard, 'setRequest');

            return $guard;
        });
    }

    protected function socialiteServiceBoot(): void
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
