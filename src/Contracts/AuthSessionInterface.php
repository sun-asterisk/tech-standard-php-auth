<?php

namespace SunAsterisk\Auth\Contracts;

use Illuminate\Http\Request;

interface AuthSessionInterface
{
    /**
     * [login]
     * @param  array         $credentials [The user's attributes for authentication.]
     * @param  array|null    $conditions  [The conditions use when query.]
     * @param  callable|null $callback    [The callback function has the entity model.]
     * @return [bool]
     */
    public function login(array $credentials = [], ?array $conditions = [], ?callable $callback = null): bool;

    /**
     * [logout]
     * @param  Illuminate\Http\Request $request [Request from controller]
     * @return [void]
     */
    public function logout(Request $request): void;

    /**
     * [register]
     * @param  array         $fields    [The user's attributes for register.]
     * @param  array         $rules     [The rules for register validate.]
     * @param  callable|null $callback  [The callback function has the entity model.]
     * @param  bool $setGuard  [The setGuard allow authenticated after register.]
     * @return [array]
     */
    public function register(
        array $params = [],
        array $rules = [],
        callable $callback = null,
        bool $setGuard = false
    ): bool;

    /**
     * [postForgotPassword]
     * @param  string        $email     [The user's email for receive token.]
     * @param  callable|null $callback  [The callback function have the token & entity model.]
     * @return [bool]
     */
    public function postForgotPassword(string $email, callable $callback = null): bool;
}
