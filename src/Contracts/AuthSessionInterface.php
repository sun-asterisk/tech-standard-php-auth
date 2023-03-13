<?php

namespace SunAsterisk\Auth\Contracts;

interface AuthSessionInterface
{
    public function login(array $credentials = [], ?array $attributes = [], ?callable $callback = null): bool;

    public function logout(): bool;
}
