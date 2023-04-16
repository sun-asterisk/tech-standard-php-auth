<?php

namespace SunAsterisk\Auth\Tests\Services;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Services\AuthSocialService;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\Provider;
use Illuminate\Http\RedirectResponse;
use Mockery;

/**
 * @covers \SunAsterisk\Auth\Services\AuthSocialService
 */
final class AuthSocialServiceTest extends TestCase
{
    protected $provider;
    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = Mockery::mock(Provider::class)->makePartial();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_social_sign_in_success()
    {
        $service = new AuthSocialService();

        $this->provider->shouldReceive('redirect')->once()->andReturn(Mockery::mock(RedirectResponse::class)->makePartial());
        Socialite::shouldReceive('driver')->with('provider')->once()->andReturn($this->provider);

        $actual = $service->socialSignIn('provider');
        $this->assertInstanceOf(RedirectResponse::class, $actual);
    }

    public function test_social_sign_in_exception()
    {
        $service = new AuthSocialService();

        Socialite::shouldReceive('driver')->with('provider')->once()->andThrow(new \Exception());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('provider is invalid!');

        $service->socialSignIn('provider');
    }

    public function test_social_callback_success()
    {
        $service = new AuthSocialService();
        $expected = (object)[];
        $this->provider->shouldReceive('user')->once()->andReturn($expected);
        Socialite::shouldReceive('driver')->with('provider')->once()->andReturn($this->provider);

        $actual = $service->socialCallback('provider');

        $this->assertEquals($actual, $expected);
    }

    public function test_social_callback_exception()
    {
        $service = new AuthSocialService();

        $this->provider->shouldReceive('user')->once()->andThrow(new \Exception());
        Socialite::shouldReceive('driver')->with('provider')->once()->andReturn($this->provider);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('provider is invalid!');

        $service->socialCallback('provider');
    }
}
