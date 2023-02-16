<?php

namespace SunAsterisk\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SunJWT
{
    /**
     * The TTL.
     *
     * @var int
     */
    protected $ttl = 60;

    /**
     * [$jwtKey]
     * @var string
     */
    protected $jwtKey;

    /**
     * Number of minutes from issue date in which a JWT can be refreshed.
     *
     * @var int
     */
    protected $rttl = 20160;

    /**
     * [$jwtRefreshKey]
     * @var string
     */
    protected $jwtRefreshKey;

    /**
     * [$payload]
     * @var array
     */
    protected $payload = [];

    /**
     * The blacklist.
     *
     * @var SunAsterisk\Auth\SunBlacklist
     */
    protected $blackList = null;

    public function __construct(SunBlacklist $blacklist = null, array $configs)
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

        $this->blackList = $blacklist;
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

    public function decode(string $token, bool $isRefresh = false, $checkBlacklist = true): array
    {
        $key = $isRefresh ? $this->jwtRefreshKey : $this->jwtKey;

        $payload = (array) JWT::decode($token, new Key($key, 'HS256'));

        if ($this->blackList && $checkBlacklist && $this->blackList->has($payload)) {
            throw new Exceptions\JWTException('The token has been blacklisted.');
        }

        return $payload;
    }

    public function invalidate(string $token, bool $isRefresh = false): bool
    {
        if (! $this->blackList) {
            throw new Exceptions\JWTException('You must have the blacklist enabled to invalidate a token.');
        }

        $payload = $this->decode($token, $isRefresh, false);

        return $this->blackList->add($payload);
    }

    public function make(array $sub, $isRefresh = false): self
    {
        if (empty($this->payload)) {
            $now = Carbon::now();
            $this->payload = [
                'sub' => $sub,
                'iat' => $now->timestamp,
                'jti' => Str::random(6) . $now->timestamp,
            ];
        }

        $iat = $this->payload['iat'];
        $ttl = $isRefresh ? $this->rttl : $this->ttl;

        $this->payload['exp'] = Carbon::createFromTimestamp($iat)->addMinutes($ttl)->timestamp;

        return $this;
    }
}
