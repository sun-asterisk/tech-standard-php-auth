<?php

namespace SunAsterisk\Auth\Contracts;

interface AuthJWTInterface
{
    /**
     * [login]
     * @param  array         $credentials
     * @param  array|null    $attributes
     * @param  callable|null $callback
     * @return [array]
     */
    public function login(array $credentials = [], ?array $attributes = [], ?callable $callback = null): array;

    /**
     * [refresh]
     * @param  string $refreshToken
     * @param  callable|null $callback
     * @return [array]
     */
    public function refresh(?string $refreshToken, callable $callback = null): array;

    /**
     * [register]
     * @param  array         $fields
     * @param  array         $rules
     * @param  callable|null $callback
     * @return [array]
     */
    public function register(array $params = [], array $rules = [], callable $callback = null): array;

    /**
     * [postForgotPassword]
     * @param  string        $email
     * @param  callable|null $callback
     * @return [bool]
     */
    public function postForgotPassword(string $email, callable $callback = null): bool;

    /**
     * [changePassword]
     * @param  array         $params
     * @param  int|null      $userId
     * @param  callable|null $callback
     * @return [bool]
     */
    public function changePassword(array $params = [], ?int $userId = null, callable $callback = null): bool;

    /**
     * [verifyForgotPasswordToken]
     * @param  string        $token
     * @param  callable|null $callback
     * @return [bool]
     */
    public function verifyToken(string $token, callable $callback = null): bool;
}
