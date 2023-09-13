<?php

namespace SunAsterisk\Auth\Tests\Services;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Contracts\RepositoryInterface;
use SunAsterisk\Auth\SunJWT;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use SunAsterisk\Auth\Services\AuthJWTService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Lang;
use Carbon\Carbon;
use Mockery;

/**
 * @covers \SunAsterisk\Auth\Services\AuthJWTService
 */
final class AuthJWTServiceTest extends TestCase
{
    protected $repository;
    protected $jwt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(RepositoryInterface::class)->makePartial();
        $this->jwt = Mockery::mock(SunJWT::class)->makePartial();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_login_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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
            ->with(['email' => 'username_val'], [])
            ->andReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A facade root has not been set.');

        $service->login(
            ['username' => 'username_val'],
            [],
        );
    }

    public function test_login_success()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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
            ->willReturn(['id', 'email']);

        $model = Mockery::mock(Model::class)->makePartial();
        $model->shouldReceive('getAttributes')
            ->once()
            ->andReturn([
                'id' => 1, 
                'email' => 'email',
            ]);
        Hash::shouldReceive('check')->once()->andReturn(true);
        $this->jwt->shouldReceive('make->toArray')->twice()->andReturn(
            [
                'jti' => 'jti',
                'exp' => 'exp',
            ]
        );
        $this->jwt->shouldReceive('encode')->twice()->andReturn('secret_key');

        $this->repository
            ->shouldReceive('findByCredentials')
            ->once()
            ->with(['email' => 'username_val'], [])
            ->andReturn($model);

        $actual = $service->login(
            [
                'username' => 'username_val',
                'passwd' => 'password',
            ],
            [],
            function () { return []; },
        );

        $expected = [
            'item' => [],
            'auth' => [
                'refresh_token' => 'secret_key',
                'access_token' => 'secret_key',
                'token_type' => 'bearer',
                'expires_at' => 'exp',
            ],
        ];

        $this->assertTrue($expected === $actual);
    }

    public function test_refresh_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $this->jwt->shouldReceive('decode')->once()->andReturn();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Undefined array key "exp"');

        $service->refresh('');
    }

    public function test_refresh_payload_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $this->jwt->shouldReceive('decode')->once()->andReturn([
            'exp' => '123',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The RefreshToken is invalid.');

        $service->refresh('');
    }

    public function test_refresh_item_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $exp = Carbon::now()->addMinutes(10)->timestamp;
        $this->jwt->shouldReceive('decode')->once()->andReturn([
            'exp' => $exp,
            'sub' => (object) ['id' => 1],
        ]);

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The RefreshToken is invalid.');

        $service->refresh('');
    }

    public function test_refresh_success()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $exp = Carbon::now()->addMinutes(10)->timestamp;
        $this->jwt->shouldReceive('decode')->once()->with('refresh_token', true)->andReturn([
            'exp' => $exp,
            'sub' => (object) ['id' => 1],
        ]);

        $model = Mockery::mock(Model::class)->makePartial();

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($model);
        $this->jwt->shouldReceive('make->toArray')->once()->andReturn(['exp' => $exp]);
        $this->jwt->shouldReceive('encode')->once()->with(['exp' => $exp])->andReturn('secret_key');

        $actual = $service->refresh('refresh_token', function () { });

        $expected = [
            'refresh_token' => 'refresh_token',
            'access_token' => 'secret_key',
            'token_type' => 'bearer',
            'expires_at' => $exp,
        ];

        $this->assertTrue($expected === $actual);
    }

    public function test_revoke_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $this->jwt->shouldReceive('revoke')->once()->with([])->andThrow(new \Exception());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Revoke token is wrong.');

        $service->revoke([]);
    }

    public function test_revoke_success()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $this->jwt->shouldReceive('revoke')->once()->with([])->andReturn(true);

        $this->assertTrue($service->revoke([]));
    }

    public function test_register()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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

        $model = Mockery::mock(Model::class)->makePartial();
        Hash::shouldReceive('make')->once()->with('password')->andReturn('hash_pw');
        $expected = [];
        $model->shouldReceive('toArray')->once()->andReturn($expected);

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
        );

        $this->assertTrue($expected === $actual);
    }

    public function test_post_forgot_password_email_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $this->repository
            ->shouldReceive('getFillable')
            ->once()
            ->andReturn([]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Model is have not the email attribute.');

        $service->postForgotPassword('email');
    }

    public function test_post_forgot_password_model_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $this->repository
            ->shouldReceive('getFillable')
            ->once()
            ->andReturn(['email']);

        ValidatorFacade::shouldReceive('make->validate')->once()->andReturn(true);

        $this->repository
            ->shouldReceive('findByAttribute')
            ->once()
            ->with(['email' => 'email'])
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A facade root has not been set.');

        $service->postForgotPassword('email');
    }

    public function test_post_forgot_password_success()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        $this->repository
            ->shouldReceive('getFillable')
            ->once()
            ->andReturn(['email']);

        $model = Mockery::mock(Model::class)->makePartial();
        ValidatorFacade::shouldReceive('make->validate')->once()->andReturn(true);

        $this->repository
            ->shouldReceive('findByAttribute')
            ->once()
            ->with(['email' => 'email'])
            ->andReturn($model);

        Crypt::shouldReceive('encryptString')->once()->andReturn('encrypt_string');

        $this->assertTrue($service->postForgotPassword('email', function () {}));
    }

    public function test_change_password_item_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods([
                'verifyToken',
                'passwd',
            ])
            ->getMock();

        $model = Mockery::mock(Model::class)->makePartial();
        $model->id = 1;
        $service->expects($this->once())
            ->method('verifyToken')
            ->will($this->returnCallback(function($string, $closure) use ($model) {
                $closure($model);

                return true;
            }));
        $service->expects($this->any())
            ->method('passwd')
            ->willReturn('password');

        ValidatorFacade::shouldReceive('make->validate')->once()->andReturn(true);
        Hash::shouldReceive('make')->once()->with('password')->andReturn('hash_pw');

        $this->repository
            ->shouldReceive('updateById')
            ->once()
            ->with(1, ['password' => 'hash_pw'])
            ->andReturn(true);

        $actual = $service->changePassword([
            'token' => 'token',
            'password' => 'password',
        ], null, function () {});

        $this->assertTrue($actual);
    }

    public function test_change_password_user_id_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods([
                'passwd',
            ])
            ->getMock();

        $service->expects($this->any())
            ->method('passwd')
            ->willReturn('password');

        $model = Mockery::mock(Model::class)->makePartial();
        ValidatorFacade::shouldReceive('make->validate')->once()->andReturn(true);
        Hash::shouldReceive('check')->once()->andReturn(false);

        $this->repository
            ->shouldReceive('findByAttribute')
            ->once()
            ->with(['id' => 1])
            ->andReturn($model);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Old password is invalid!');

        $actual = $service->changePassword([
            'password' => 'password',
        ], 1, function () {});
    }

    public function test_verify_token_item_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                [],
            ])
            ->setMethods()
            ->getMock();

        Crypt::shouldReceive('decryptString')->once()->with('token')->andReturn(json_encode(['id' => 1]));

        $this->repository
            ->shouldReceive('findByAttribute')
            ->once()
            ->with(['id' => 1])
            ->andReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token is invalid!');

        $actual = $service->verifyToken('token', function () {});
    }

    public function test_verify_token_token_expires_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                ['token_expires' => 10],
            ])
            ->setMethods()
            ->getMock();

        Crypt::shouldReceive('decryptString')->once()->with('token')->andReturn(json_encode([
            'id' => 1,
            'created_at' => Carbon::now()->subMinutes(20)->timestamp,
        ]));

        $model = Mockery::mock(Model::class)->makePartial();

        $this->repository
            ->shouldReceive('findByAttribute')
            ->once()
            ->with(['id' => 1])
            ->andReturn($model);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Token is invalid!');

        $actual = $service->verifyToken('token', function () {});
    }

    public function test_verify_token_exception()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                ['token_expires' => 10],
            ])
            ->setMethods()
            ->getMock();

        Crypt::shouldReceive('decryptString')->once()->with('token')->andReturn('');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Trying to access array offset on value of type null');

        $actual = $service->verifyToken('token', function () {});
    }

    public function test_verify_token_success()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                ['token_expires' => 30],
            ])
            ->setMethods()
            ->getMock();

        Crypt::shouldReceive('decryptString')->once()->with('token')->andReturn(json_encode([
            'id' => 1,
            'created_at' => Carbon::now()->subMinutes(20)->timestamp,
        ]));

        $model = Mockery::mock(Model::class)->makePartial();

        $this->repository
            ->shouldReceive('findByAttribute')
            ->once()
            ->with(['id' => 1])
            ->andReturn($model);

        $actual = $service->verifyToken('token', function () {});

        $this->assertTrue($actual);
    }

    public function test_login_validator()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                ['field_credentials' => ['field']],
            ])
            ->setMethods()
            ->getMock();

        $equal = $this->callMethodProtectedOrPrivate($service, 'fieldCredentials', []);
        $this->assertEquals($equal, ['field']);
    }

    public function test_username()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                ['login_username' => 'username'],
            ])
            ->setMethods()
            ->getMock();

        $equal = $this->callMethodProtectedOrPrivate($service, 'username', []);
        $this->assertEquals($equal, 'username');
    }

    public function test_passwd()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
                ['login_password' => 'password'],
            ])
            ->setMethods()
            ->getMock();

        $equal = $this->callMethodProtectedOrPrivate($service, 'passwd', []);
        $this->assertEquals($equal, 'password');
    }

    public function test_get_failed_login_message_not_has()
    {
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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
        $service = $this->getMockBuilder(AuthJWTService::class)
            ->setConstructorArgs([
                $this->repository,
                $this->jwt,
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
