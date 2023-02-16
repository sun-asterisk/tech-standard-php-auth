<?php

namespace SunAsterisk\Auth\Exceptions;

use RuntimeException;

class JWTException extends RuntimeException
{
    protected $code = 40003;
}
