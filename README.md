# Tech Standard PHP Auth

## Table of Contents
- [Laravel Installation](#laravel-installation)
- [Lumen Installation](#lumen-installation)
- [Other Installation](#other-installation)
- [Quick start](#quick-start)
- [Wiki](../../wiki)

## Overview

## Integrations for Laravel, Lumen and Symfony are available:
```bash
composer require sun-asterisk/php-auth
```
### Laravel Installation
```php
<?php
// config/app.php
return [
    // ...
    'providers' => [
        // ...
        SunAsterisk\Auth\SunServiceProvider::class
    ]
    // ...
];
```
Configuration
```bash
php artisan vendor:publish --provider="SunAsterisk\Auth\SunServiceProvider" --tag=sun-asterisk
```

The Sun-asterisk PHP SDK is available on Packagist as sun-asterisk/php-auth:

```bash
composer require sun-asterisk/php-auth
```

### Lumen Installation
```php
<?php
// bootstrap/app.php

$app->register(SunAsterisk\Auth\SunServiceProvider::class);
```
Configuration
```bash
mkdir -p config
cp vendor/sun-asterisk/config/sun-asterisk.php config/sun-asterisk.php
```
### Other Installation
```php
use SunAsterisk\Auth\Factory;

$configs = config('/path/to/sun-asterisk.php')

$factory = (new Factory)->withConfig($configs);
$service = $factory->createAuthJwt();
```
Configuration
```bash
mkdir -p config
cp vendor/sun-asterisk/config/sun-asterisk.php config/sun-asterisk.php
```
## Quick start
### Configure Auth guard
Inside the config/auth.php file you will need to make a few changes to configure
```php
'guards' => [
    'api' => [
        'driver' => 'sun',
        'provider' => 'users',
    ],
],
```
### Add some basic authentication routes
First let's add some routes in routes/api.php as follows:
```php
Route::group([
    'middleware' => 'auth:api',
], function ($router) {
    Route::post('logout', 'AuthController@logout');
    Route::get('me', 'AuthController@me');
    Route::post('change-password', 'AuthController@changePassword');
});

Route::post('refresh', 'AuthController@refresh');
Route::post('login', 'AuthController@login');
Route::post('register', 'AuthController@register');
Route::post('forgot-password', 'AuthController@postForgotPassword');
Route::get('confirm', 'AuthController@confirm');
Route::post('new-password', 'AuthController@postNewPassword');
```

### Create the AuthController
```php
php artisan make:controller AuthController
```
Then add the following:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SunAsterisk\Auth\Contracts\AuthJWTInterface;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    protected AuthJWTInterface $service;

    public function __construct(AuthJWTInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $params = $request->only(['username', 'password']);

        $rs = $this->service->login($params, [], function ($entity) {
            return $entity->only(['id', 'email', 'username']);
        });

        return response()->json($rs['auth']);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        return response()->noContent();
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $me = $request->user('api');
        unset($me->email_verified_at);
        unset($me->remember_token);

        return response()->json($me);
    }

    /**
     * Refresh token the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        $token = $request->refresh_token;

        $rs = $this->service->refresh($token);

        return response()->json($rs);
    }

    /**
     * create the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $rules = [];
        $fields = $request->only(['username', 'password', 'email']);
        $fields['name'] = $fields['username'];

        $result = $this->service->register($fields, $rules, function ($entity) {
            return $entity->only(['id', 'email', 'username']);
        });

        return response()->json($result);
    }

    /**
     * Create the token forgot password.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postForgotPassword(Request $request)
    {
        $email = $request->email;
        $status = $this->service->postForgotPassword($email, function ($token, $user) {
            // Use send mail from framework
            Mail::send([], [], function ($message) use ($token, $user) {
                $message->to($user->email)
                    ->setBody("Hi, Your token verify is {$token}");
                });
        });

        return response()->json([
            'ok' => $status,
            'type' => 'forgotPassword',
        ]);
    }

    /**
     * Update New password.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postNewPassword(Request $request)
    {
        $params = $request->only(['password', 'token']);
        $token = $request->token;
        $status = $this->service->changePassword($params, null, function ($user, &$attr) {
            // Update attr
        });

        return response()->json([
            'ok' => $status,
            'type' => 'postNewPassword',
        ]);
    }

    /**
     * Verify the token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirm(Request $request)
    {
        $token = $request->token;
        $status = $this->service->verifyToken($token);

        return response()->json([
            'ok' => $status,
        ]);
    }

    /**
     * Change password
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        $params = $request->only(['old_password', 'password']);
        $user = $request->user('api');

        $status = $this->service->changePassword($params, $user?->id, function ($user, &$attr) {
            // Update Attr
        });

        return response()->json([
            'ok' => $status,
            'type' => 'changePassword',
        ]);
    }
}

```
