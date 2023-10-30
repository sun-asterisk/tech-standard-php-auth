<?php

namespace SunAsterisk\Auth;

use SunAsterisk\Auth\Contracts\StorageInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class Factory
{
    /**
     * @var array $config
     */
    private array $config = [];

    /**
     * @var \SunAsterisk\Auth\SunBlacklist
     */
    private $blackList = null;

    /**
     * @var guard
     */
    private $guard = null;

    /**
     * @var \SunAsterisk\Auth\SunTokenMapper
     */
    private $tokenMapper = null;

    public function __construct()
    {
        //
    }

    public function withConfig(array $config = []): self
    {
        $this->config = $config;

        return $this;
    }

    public function withBlacklist(Contracts\StorageInterface $storage): self
    {
        $this->blackList = new SunBlacklist($storage);

        return $this;
    }

    public function withGuard($guard): self
    {
        $this->guard = $guard;

        return $this;
    }

    public function withTokenMapper(StorageInterface $storage): self
    {
        $this->tokenMapper = new SunTokenMapper($storage);

        return $this;
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
                // Eloquent of Laravel & lumen
                $repository = new Repositories\EloquentRepository($model);
                break;
            default:
                // TODO
                break;
        }
        if (!isset($repository)) {
            throw new InvalidArgumentException('Repository is invalid.');
        }
        // Create JWT
        $jwt = new SunJWT($this->blackList, $this->config['auth']);

        return new Services\AuthJWTService(
            $repository,
            $jwt,
            $this->tokenMapper,
            $this->config['auth']
        );
    }

    public function createAuthSession(): Contracts\AuthSessionInterface
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
                // Eloquent of Laravel & lumen
                $repository = new Repositories\EloquentRepository($model);
                break;
            default:
                // TODO
                break;
        }
        if (!isset($repository)) {
            throw new InvalidArgumentException('Repository is invalid.');
        }

        // Create guard
        if (!$this->guard) {
            throw new InvalidArgumentException('guard is invalid.');
        }

        return new Services\AuthSessionService($repository, $this->guard, $this->config['auth']);
    }

    public function createAuthSocial(): Contracts\AuthSocialInterface
    {
        return new Services\AuthSocialService();
    }
}
