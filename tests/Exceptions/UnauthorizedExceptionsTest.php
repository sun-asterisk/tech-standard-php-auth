<?php

namespace SunAsterisk\Auth\Tests\Exceptions;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Exceptions\UnauthorizedException;

/**
 * @covers \SunAsterisk\Auth\Exceptions\UnauthorizedException
 */
final class UnauthorizedExceptionTest extends TestCase
{
    public function test_protected()
    {
        $exception = new UnauthorizedException();
        $actual = $this->callAttributeProtectedOrPrivate($exception, 'code');

        $this->assertEquals($actual, 40001);
    }

    public function test_message()
    {
        $exception = new UnauthorizedException();
        $actual = $this->callAttributeProtectedOrPrivate($exception, 'message');

        $this->assertEquals($actual, 'UnauthorizedException.');
    }
}
