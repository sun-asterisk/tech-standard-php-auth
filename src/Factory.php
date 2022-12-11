<?php

namespace SunAsterisk\Auth;

use Illuminate\Database\Eloquent\Model;
use SunAsterisk\Auth\Contracts;
use SunAsterisk\Auth\Repository\EloquentRepository;
use InvalidArgumentException;

final class Factory
{
    /**
     * @var array $config
     */
    private array $config = [];

    public function __construct()
    {
        //
    }

    public function withConfig(array $config = []): self
    {
        $factory = clone $this;
        $factory->config = $config;

        return $factory;
    }

    public function createAuthJWT(): Contracts\AuthJWTInterface
    {
        if (empty($this->config['auth'])) {
            throw new InvalidArgumentException('Config is invalid.');
        }
        $model = $this->config['auth']['model'];

        if (is_string($model) && class_exists($model)) {
            $model = new $model();
        }

        switch (true) {
            case $model instanceof Model:
                // Eloquent of Laravel
                $repository = new EloquentRepository($model);
                break;
            default:
                // TODO
                break;
        }
        if (!isset($repository)) {
            throw new InvalidArgumentException('Repository is invalid.');
        }

        return new AuthJWTService($repository, $this->config['auth']);
    }

    public function createAuthSocial(): Contracts\AuthSocialInterface
    {
        return new AuthSocialService();
    }
}
