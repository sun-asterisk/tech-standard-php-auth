<?php

namespace SunAsterisk\Auth;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Http\Request;
use SunAsterisk\Auth\SunJWT;
use SunAsterisk\Auth\Exception\JWTException;

class SunGuard implements Guard
{
    use GuardHelpers, Macroable;

    protected $jwt;
    /**
     * The provider instance.
     *
     * @var \Illuminate\Contracts\Auth\UserProvider
     */
    protected $provider;

    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Instantiate the class.
     *
     * @param  \SunAsterisk\Auth\SunJWT  $jwt
     * @param  \Illuminate\Contracts\Auth\UserProvider  $provider
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function __construct(SunJWT $jwt, UserProvider $provider, Request $request)
    {
        $this->jwt = $jwt;
        $this->provider = $provider;
        $this->request = $request;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        try {
            $token = $this->request->bearerToken();
            $payload = $this->jwt->decode($token ?: '');
        } catch (\Exception $e) {
            throw new JWTException('UnauthorizedException');
        }

        return $payload['sub'];
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        //
    }

    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
