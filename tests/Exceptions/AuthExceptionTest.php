<?php

namespace SunAsterisk\Auth\Tests\Exceptions;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Exceptions\AuthException;

/**
 * @covers \SunAsterisk\Auth\Exceptions\AuthException
 */
final class AuthExceptionTest extends TestCase
{
    public function test_protected()
    {
        $exception = new AuthException();
        $actual = $this->callAttributeProtectedOrPrivate($exception, 'code');

        $this->assertEquals($actual, 40002);
    }
}
