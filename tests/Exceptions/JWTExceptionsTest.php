<?php

namespace SunAsterisk\Auth\Tests\Exceptions;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Exceptions\JWTException;

/**
 * @covers \SunAsterisk\Auth\Exceptions\JWTException
 */
final class JWTExceptionTest extends TestCase
{
    public function test_protected()
    {
        $exception = new JWTException();
        $actual = $this->callAttributeProtectedOrPrivate($exception, 'code');

        $this->assertEquals($actual, 40003);
    }
}
