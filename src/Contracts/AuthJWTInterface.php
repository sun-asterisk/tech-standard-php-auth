<?php

namespace SunAsterisk\Auth\Contracts;

interface AuthJWTInterface
{
    /**
     * [login]
     * @param  array         $credentials [The user's attributes for authentication.]
     * @param  array|null    $conditions  [The conditions use when query.]
     * @param  callable|null $callback    [The callback function has the entity model.]
     * @return [array]
     */
    public function login(array $credentials = [], ?array $conditions = [], ?callable $callback = null): array;

    /**
     * [refresh]
     * @param  string $refreshToken     [refresh_token for user get access_token.]
     * @param  callable|null $callback  [The callback function has the entity model.]
     * @return [array]
     */
    public function refresh(?string $refreshToken, callable $callback = null): array;

    /**
     * [revoke]
     * @param  array  $keys [keys were generated from each access token]
     * @return [bool]
     */
    public function revoke(array $keys = []): bool;

    /**
     * [register]
     * @param  array         $fields    [The user's attributes for register.]
     * @param  array         $rules     [The rules for register validate.]
     * @param  callable|null $callback  [The callback function has the entity model.]
     * @return [array]
     */
    public function register(array $params = [], array $rules = [], callable $callback = null): array;

    /**
     * [postForgotPassword]
     * @param  string        $email     [The user's email for receive token.]
     * @param  callable|null $callback  [The callback function have the token & entity model.]
     * @return [bool]
     */
    public function postForgotPassword(string $email, callable $callback = null): bool;

    /**
     * [changePassword]
     * @param  array         $params    [The params for change password (passwd | ?old_passwd | ?token)]
     * @param  int|null      $userId    [The user's id when user authenticate.]
     * @param  callable|null $callback  [The callback function have the entity model & pointer of users's attributes.]
     * @return [bool]
     */
    public function changePassword(array $params = [], ?int $userId = null, callable $callback = null): bool;

    /**
     * [verifyForgotPasswordToken]
     * @param  string        $token     [The token from user's email.]
     * @param  callable|null $callback  [The callback function has the token & entity model.]
     * @return [bool]
     */
    public function verifyToken(string $token, callable $callback = null): bool;

    /**
     * Invalidate a token.
     * @param  string $token The user token
     * @param  bool   $isRefresh True if the token is a refresh token
     * @return bool
     */
    public function invalidate(string $token, bool $isRefresh = false): bool;
}
