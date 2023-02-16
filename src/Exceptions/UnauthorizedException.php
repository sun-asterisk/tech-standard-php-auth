<?php

namespace SunAsterisk\Auth\Exceptions;

use RuntimeException;

class UnauthorizedException extends RuntimeException
{
    protected $message = 'UnauthorizedException.';
    protected $code = 40001;
}
