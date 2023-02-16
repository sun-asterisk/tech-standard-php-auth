<?php

namespace SunAsterisk\Auth;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class Factory
{
    /**
     * @var array $config
     */
    private array $config = [];

    /**
     * @var SunAsterisk\Auth\SunBlacklist
     */
    private $blackList = null;

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

        return new Services\AuthJWTService($repository, $jwt, $this->config['auth']);
    }

    public function createAuthSocial(): Contracts\AuthSocialInterface
    {
        return new Services\AuthSocialService();
    }
}
