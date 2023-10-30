<?php

namespace SunAsterisk\Auth;

use Carbon\Carbon;
use SunAsterisk\Auth\Contracts\StorageInterface;

class SunTokenMapper
{
    const KEY = 'sa_tokens_mapper:__JTI__';

    /**
     * @var \SunAsterisk\Auth\Contracts\StorageInterface
     */
    protected $storage = null;

    public function __construct(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    private function getKey(string $accessTokenJti): string
    {
        return str_replace('__JTI__', $accessTokenJti, self::KEY);
    }

    public function add(array $accessTokenPayload, string $refreshToken)
    {
        $cacheKey = $this->getKey($accessTokenPayload['jti']);
        if ($this->storage->has($cacheKey)) return;

        $valid = $accessTokenPayload['exp'];
        $now = Carbon::now();
        if ($valid <= $now->timestamp) return;

        $diffInSeconds = $now->diffInSeconds(Carbon::createFromTimestamp($valid));
        $this->storage->add(
            $cacheKey,
            $refreshToken,
            $diffInSeconds
        );
    }

    public function getRefreshToken(string $accessTokenJti): ?string
    {
        return $this->storage->get($this->getKey($accessTokenJti));
    }

    public function pullRefreshToken(string $accessTokenJti): ?string
    {
        return $this->storage->pull($this->getKey($accessTokenJti));
    }
}
