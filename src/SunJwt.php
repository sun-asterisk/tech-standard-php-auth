<?php

namespace SunAsterisk\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

class SunJWT
{
    protected $ttl = 60;

    protected $jwtKey;

    protected $rttl = 20160;

    protected $jwtRefreshKey;

    protected $payload = [];

    public function __construct(array $configs)
    {
        if (isset($configs['jwt_ttl']) && is_numeric($configs['jwt_ttl'])) {
            $this->ttl = $configs['jwt_ttl'];
        }

        if (!empty($configs['jwt_key']) || getenv('APP_JWT_KEY')) {
            $this->jwtKey = getenv('APP_JWT_KEY') ?: $configs['jwt_key'];
        }

        if (isset($configs['jwt_refresh_ttl']) && is_numeric($configs['jwt_refresh_ttl'])) {
            $this->rttl = $configs['jwt_refresh_ttl'];
        }

        if (!empty($configs['jwt_refresh_key']) || getenv('APP_JWT_REFRESH_KEY')) {
            $this->jwtRefreshKey = getenv('APP_JWT_REFRESH_KEY') ?: $configs['jwt_refresh_key'];
        }
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    public function encode(array $payload, bool $isRefresh = false): string
    {
        $key = $isRefresh ? $this->jwtRefreshKey : $this->jwtKey;

        return JWT::encode($payload, $key, 'HS256');
    }

    public function decode(string $token, bool $isRefresh = false): array
    {
        $key = $isRefresh ? $this->jwtRefreshKey : $this->jwtKey;

        return (array) JWT::decode($token, new Key($key, 'HS256'));
    }

    public function invalidate(string $token)
    {
        //
    }

    public function make(array $sub, $isRefresh = false): self
    {
        if (empty($this->payload)) {
            $now = Carbon::now();
            $this->payload = [
                'sub' => $sub,
                'iat' => $now->timestamp,
            ];
        }

        $iat = $this->payload['iat'];
        $ttl = $isRefresh ? $this->rttl : $this->ttl;

        $this->payload['exp'] = Carbon::createFromTimestamp($iat)->addMinutes($ttl)->timestamp;

        return $this;
    }
}
