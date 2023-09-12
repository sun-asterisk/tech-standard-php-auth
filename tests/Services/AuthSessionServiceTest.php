<?php

namespace SunAsterisk\Auth\Tests\Services;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Contracts\RepositoryInterface;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use SunAsterisk\Auth\Services\AuthSessionService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Request;
use Mockery;

/**
 * @covers \SunAsterisk\Auth\Services\AuthSessionService
 */
final class AuthSessionServiceTest extends TestCase
{
    protected $repository;
    protected $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(RepositoryInterface::class)->makePartial();
        $this->guard = Mockery::mock(StatefulGuard::class)->makePartial();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_login_exception()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods([
                'loginValidator',
                'username',
                'fieldCredentials',
                'payloadCredentials',
            ])
            ->getMock();

        $validator = Mockery::mock(Validator::class)->makePartial();
        $validator->shouldReceive('validate')->once()->andReturn(true);
        $service->expects($this->any())
            ->method('loginValidator')
            ->willReturn($validator);

        $service->expects($this->any())
            ->method('username')
            ->willReturn('username');

        $service->expects($this->any())
            ->method('fieldCredentials')
            ->willReturn(['email']);

        $service->expects($this->any())
            ->method('payloadCredentials')
            ->willReturn(['id', 'email', 'password']);

        $this->repository
            ->shouldReceive('findByCredentials')
            ->once()
            ->with(['email' => 'username_val'], [], ['id', 'email', 'password'])
            ->andReturn(null);
        $this->expectException(\Exception::class);

        $service->login(
            ['username' => 'username_val'],
            [],
        );
    }

    public function test_login_success()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods([
                'loginValidator',
                'username',
                'passwd',
                'fieldCredentials',
                'payloadCredentials',
            ])
            ->getMock();

        $validator = Mockery::mock(Validator::class)->makePartial();
        $validator->shouldReceive('validate')->once()->andReturn(true);
        $service->expects($this->any())
            ->method('loginValidator')
            ->willReturn($validator);

        $service->expects($this->any())
            ->method('username')
            ->willReturn('username');

        $service->expects($this->any())
            ->method('passwd')
            ->willReturn('passwd');

        $service->expects($this->any())
            ->method('fieldCredentials')
            ->willReturn(['email']);

        $service->expects($this->any())
            ->method('payloadCredentials')
            ->willReturn(['id', 'email', 'password']);
    
        $model = Mockery::mock(Authenticatable::class)->makePartial();
        $model->passwd = '';
        Hash::shouldReceive('check')->once()->andReturn(true);
        $this->guard->shouldReceive('login')->once()->with($model, false);

        $this->repository
            ->shouldReceive('findByCredentials')
            ->once()
            ->with(['email' => 'username_val'], [], ['id', 'email', 'password'])
            ->andReturn($model);

        $actual = $service->login(
            [
                'username' => 'username_val',
                'passwd' => 'password',
            ],
            [],
            function () { return []; },
        );

        $this->assertTrue($actual);
    }

    public function test_logout()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods()
            ->getMock();
        $this->guard->shouldReceive('logout')->once();
        $request = Mockery::mock(Request::class)->makePartial();
        $request->shouldReceive('session->invalidate')->once();
        $request->shouldReceive('session->regenerateToken')->once();

        $service->logout($request);

        $this->assertTrue(true);
    }

    public function test_register()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods([
                'loginValidator',
                'username',
                'passwd',
                'fieldCredentials',
            ])
            ->getMock();
        $this->repository
            ->shouldReceive('getTable')
            ->once()
            ->andReturn('table');

        ValidatorFacade::shouldReceive('make->validate')->once()->andReturn(true);

        $service->expects($this->any())
            ->method('passwd')
            ->willReturn('passwd');

        $service->expects($this->any())
            ->method('fieldCredentials')
            ->willReturn(['email']);

        $model = Mockery::mock(Authenticatable::class)->makePartial();
        Hash::shouldReceive('make')->once()->with('password')->andReturn('hash_pw');

        $this->guard->shouldReceive('login')->once($model);
        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with([
                'username' => 'username_val',
                'passwd' => 'hash_pw',
            ])
            ->andReturn($model);

        $actual = $service->register(
            [
                'username' => 'username_val',
                'passwd' => 'password',
            ],
            [],
            function () { return []; },
            true,
        );

        $this->assertTrue($actual);
    }

    public function test_login_validator()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods([
                'username',
                'passwd',
            ])
            ->getMock();
        $service->expects($this->any())
            ->method('passwd')
            ->willReturn('password');
        $service->expects($this->any())
            ->method('username')
            ->willReturn('username');
        $validator = Mockery::mock(Validator::class)->makePartial();

        ValidatorFacade::shouldReceive('make')
            ->with([], [
                'username' => 'required',
                'password' => 'required',
            ])
            ->once()
            ->andReturn($validator);

        $this->callMethodProtectedOrPrivate($service, 'loginValidator', [[]]);

        $this->assertNull(null);
    }

    public function test_field_credentials()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                ['field_credentials' => ['field']],
            ])
            ->setMethods()
            ->getMock();

        $equal = $this->callMethodProtectedOrPrivate($service, 'fieldCredentials', []);
        $this->assertEquals($equal, ['field']);
    }

    public function test_username()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                ['login_username' => 'username'],
            ])
            ->setMethods()
            ->getMock();

        $equal = $this->callMethodProtectedOrPrivate($service, 'username', []);
        $this->assertEquals($equal, 'username');
    }

    public function test_passwd()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                ['login_password' => 'password'],
            ])
            ->setMethods()
            ->getMock();

        $equal = $this->callMethodProtectedOrPrivate($service, 'passwd', []);
        $this->assertEquals($equal, 'password');
    }

    public function test_get_failed_login_message_not_has()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods()
            ->getMock();
        Lang::shouldReceive('has')->once()->andReturn(false);

        $equal = $this->callMethodProtectedOrPrivate($service, 'getFailedLoginMessage', []);
        $this->assertEquals($equal, 'These credentials do not match our records.');
    }

    public function test_get_failed_login_message_has()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods()
            ->getMock();
        Lang::shouldReceive('has')->once()->andReturn(true);
        Lang::shouldReceive('get')->with('auth.failed')->once()->andReturn('auth_failed');

        $equal = $this->callMethodProtectedOrPrivate($service, 'getFailedLoginMessage', []);
        $this->assertEquals($equal, 'auth_failed');
    }

    public function test_get_email_invalid_message_not_has()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods()
            ->getMock();
        Lang::shouldReceive('has')->once()->andReturn(false);

        $equal = $this->callMethodProtectedOrPrivate($service, 'getEmailInvalidMessage', []);
        $this->assertEquals($equal, 'The email is invalid.');
    }

    public function test_get_email_invalid_message_has()
    {
        $service = $this->getMockBuilder(AuthSessionService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->guard,
                [],
            ])
            ->setMethods()
            ->getMock();
        Lang::shouldReceive('has')->once()->andReturn(true);
        Lang::shouldReceive('get')->with('validation.email', ['attribute' => 'email'])->once()->andReturn('validation_email');

        $equal = $this->callMethodProtectedOrPrivate($service, 'getEmailInvalidMessage', ['email']);
        $this->assertEquals($equal, 'validation_email');
    }
}
