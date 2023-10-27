<?php

namespace SunAsterisk\Auth;

use Carbon\Carbon;

class SunBlacklist
{
    /**
     * The storage.
     *
     */
    protected $storage;

    /**
     * The unique key held within the blacklist.
     *
     * @var string
     */
    protected $key = 'jti';

    public function __construct(Contracts\StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    public function add(array $payload)
    {
        $key = $payload[$this->key];

        // if we have already added this token to the blacklist
        if ($this->storage->has($key)) {
            return true;
        }

        // if expired less than now
        $valid = $payload['exp'];
        $now = Carbon::now();
        if ($valid <= $now->timestamp) {
            return true;
        }

        $diffInSeconds = $now->diffInSeconds(Carbon::createFromTimestamp($valid));
        $this->storage->add(
            $key,
            ['valid_until' => $valid],
            $diffInSeconds,
        );

        return true;
    }

    public function has(array $payload): bool
    {
        return $this->storage->has($payload[$this->key]);
    }
}
