<?php

namespace SunAsterisk\Auth\Providers;

use SunAsterisk\Auth\Contracts\StorageInterface;
use Illuminate\Contracts\Cache\Repository;

final class Storage implements StorageInterface
{
    /**
     * The cache repository contract.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param  \Illuminate\Contracts\Cache\Repository  $cache
     * @return void
     */
    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function add(string $key, mixed $value, int $minutes): void
    {
        $this->cache->put($key, $value, $minutes);
    }

    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function destroy(string $key): bool
    {
        return $this->cache->forget($key);
    }
}
