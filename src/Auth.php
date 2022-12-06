<?php

namespace SunAsterisk\Auth;

final class Auth extends UserManager
{
    public function __construct()
    {
        parent::__construct();
    }

    public function loginWithUsername($data, callable $callback)
    {
    }
}
