<?php

namespace SunAsterisk\Auth\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use SunAsterisk\Auth\SunJWT;
use SunAsterisk\Auth\Contracts;
use SunAsterisk\Auth\Exceptions;
use Carbon\Carbon;
use SunAsterisk\Auth\SunTokenMapper;

class AuthJWTService implements Contracts\AuthJWTInterface
{
    /**
     * The repository implementation.
     *
     * @var \SunAsterisk\Auth\Contracts\RepositoryInterface
     */
    protected $repository;

    /**
     * The jwt implementation.
     *
     * @var \SunAsterisk\Auth\SunJWT
     */
    protected $jwt;

    /**
     * The config implementation.
     *
     * @var array
     */
    private array $config = [];

    /**
     * @var \SunAsterisk\Auth\SunTokenMapper
     */
    protected $tokenMapper;

    public function __construct(
        Contracts\RepositoryInterface $repository,
        SunJWT $jwt,
        SunTokenMapper $tokenMapper,
        array $config = []
    ) {
        $this->repository = $repository;
        $this->config = $config;
        $this->jwt = $jwt;
        $this->tokenMapper = $tokenMapper;
    }

    /**
     * [login]
     * @param  array         $credentials [The user's attributes for authentication.]
     * @param  array|null    $conditions  [The conditions use when query.]
     * @param  callable|null $callback    [The callback function has the entity model.]
     * @return [array]
     */
    public function login(array $credentials = [], ?array $conditions = [], ?callable $callback = null): array
    {
        $this->loginValidator($credentials)->validate();
        $username = Arr::get($credentials, $this->username());

        $fieldCredentials = [];
        foreach ($this->fieldCredentials() as $field) {
            $fieldCredentials[$field] = $username;
        }

        $item = $this->repository->findByCredentials($fieldCredentials, $conditions);

        if (!$item || !Hash::check(Arr::get($credentials, $this->passwd()), $item->{$this->passwd()})) {
            throw ValidationException::withMessages([
                'message' => $this->getFailedLoginMessage(),
            ]);
        }

        unset($item->password);
        $itemArr = $item->toArray();

        $tokenPayload = [];
        foreach ($this->tokenPayloadFields() as $tokenPayloadField) {
            if (!array_key_exists($tokenPayloadField, $itemArr)) {
                throw ValidationException::withMessages([
                    'message' => $this->getInvalidConfigurationMessage(),
                ]);
            }
            $tokenPayload[$tokenPayloadField] = $itemArr[$tokenPayloadField];
        }

        // Create jwt key
        $payloadRefresh = $this->jwt->make($tokenPayload, true)->toArray();
        $payload = $this->jwt->make($tokenPayload)->toArray();

        if (is_callable($callback)) {
            $itemArr = call_user_func_array($callback, [$item, $payload['jti']]);
        }
        $jwt = $this->jwt->encode($payload);
        $refresh = $this->jwt->encode($payloadRefresh, true);

        $this->tokenMapper->add($payload, $refresh);

        return [
            'item' => $itemArr,
            'auth' => [
                'refresh_token' => $refresh,
                'access_token' => $jwt,
                'token_type' => 'bearer',
                'expires_at' => $payload['exp'],
            ],
        ];
    }

    /**
     * [refresh]
     * @param  string $refreshToken     [refresh_token for user get access_token.]
     * @param  callable|null $callback  [The callback function has the entity model.]
     * @return [array]
     */
    public function refresh(?string $refreshToken, callable $callback = null): array
    {
        try {
            $payload = $this->jwt->decode($refreshToken ?: '', true);
            if (Carbon::createFromTimestamp($payload['exp'])->lte(Carbon::now())) {
                throw new Exceptions\JWTException('The RefreshToken is invalid.');
            }

            $sub = $payload['sub'];
            // Verify user
            $item = $this->repository->findById($sub?->id);
            if (!$item) {
                throw new Exceptions\JWTException('The RefreshToken is invalid.');
            }
            // TODO Revoke other token

            if (is_callable($callback)) {
                call_user_func_array($callback, [$item]);
            }

            $payload = $this->jwt->make((array) $sub)->toArray();
            $jwt = $this->jwt->encode($payload);

            $this->tokenMapper->add($payload, $refreshToken);

            return [
                'refresh_token' => $refreshToken,
                'access_token' => $jwt,
                'token_type' => 'bearer',
                'expires_at' => $payload['exp'],
            ];
        } catch (\Exception $e) {
            throw new Exceptions\AuthException($e->getMessage());
        }
    }

    public function revoke(array $keys = []): bool
    {
        try {
            return $this->jwt->revoke($keys);
        } catch (\Exception $e) {
            throw new Exceptions\JWTException('Revoke token is wrong.');
        }
    }

    /**
     * [register]
     * @param  array         $fields    [The user's attributes for register.]
     * @param  array         $rules     [The rules for register validate.]
     * @param  callable|null $callback  [The callback function has the entity model.]
     * @return [array]
     */
    public function register(array $params = [], array $rules = [], callable $callback = null): array
    {
        $table = $this->repository->getTable();
        if (empty($rules)) {
            foreach ($this->fieldCredentials() as $field) {
                $rules[$field] = ['required', 'string', "unique:{$table}," . $field];
            }
            $rules[$this->passwd()] = [
                'required',
                'min:6',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%]).*$/',
            ];
        }

        Validator::make($params, $rules)->validate();
        $params[$this->passwd()] = Hash::make($params[$this->passwd()]);

        $item = $this->repository->create($params);
        $itemArr = $item->toArray();

        if (is_callable($callback)) {
            $itemArr = call_user_func_array($callback, [$item]);
        }

        return $itemArr;
    }

    /**
     * [postForgotPassword]
     * @param  string        $email     [The user's email for receive token.]
     * @param  callable|null $callback  [The callback function have the token & entity model.]
     * @return [bool]
     */
    public function postForgotPassword(string $email, callable $callback = null): bool
    {
        if (!in_array('email', $this->repository->getFillable())) {
            throw new Exceptions\AuthException('Model is have not the email attribute.');
        }
        // Validate Email
        Validator::make(['email' => $email], [
            'email' => ['required', 'email'],
        ])->validate();
        // Check Email exists
        $item = $this->repository->findByAttribute(['email' => $email]);
        if (!$item) {
            throw ValidationException::withMessages([
                'message' => $this->getEmailInvalidMessage($email),
            ]);
        }
        // Generate Token
        $obj = [
            'id' => $item->id,
            'created_at' => Carbon::now()->timestamp,
        ];

        $token = Crypt::encryptString(json_encode($obj));

        if (is_callable($callback)) {
            call_user_func_array($callback, [$token, $item]);
        }

        return true;
    }

    /**
     * [changePassword]
     * @param  array         $params    [The params for change password (passwd | ?old_passwd | ?token)]
     * @param  int|null      $userId    [The user's id when user authenticate.]
     * @param  callable|null $callback  [The callback function have the entity model & pointer of users's attributes.]
     * @return [bool]
     */
    public function changePassword(array $params = [], ?int $userId = null, callable $callback = null): bool
    {
        Validator::make($params, [
            $this->passwd() => [
                'required',
                'min:6',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%]).*$/',
            ],
        ])->validate();

        $user = null;
        $attr = [];

        // For usecase forgot password
        if (isset($params['token'])) {
            $this->verifyToken($params['token'], function ($entity) use (&$user) {
                $user = $entity;
            });
        }

        // For usecase update password
        if ($userId) {
            $oldPassword = $params['old_password'] ?? '';

            $user = $this->repository->findByAttribute(['id' => $userId]);
            if (!Hash::check($oldPassword, $user->{$this->passwd()})) {
                throw new Exceptions\AuthException('Old password is invalid!');
            }
        }

        if ($user) {
            $attr[$this->passwd()] = Hash::make($params[$this->passwd()]);
            if (is_callable($callback)) {
                call_user_func_array($callback, [$user, &$attr]);
            }

            $this->repository->updateById($user->id, $attr);
        }

        return true;
    }

    /**
     * [verifyToken]
     * @param  string        $token     [The token from user's email.]
     * @param  callable|null $callback  [The callback function has the token & entity model.]
     * @return [bool]
     */
    public function verifyToken(string $token, callable $callback = null): bool
    {
        try {
            $objStr = Crypt::decryptString($token);
            $obj = json_decode($objStr, true);

            // Check user
            $item = $this->repository->findByAttribute(['id' => $obj['id']]);
            if (!$item) {
                throw new Exceptions\AuthException('Token is invalid!');
            }
            $diffSeconds = Carbon::now()->diffInSeconds(Carbon::createFromTimestamp($obj['created_at']));

            if ($diffSeconds >= $this->config['token_expires'] * 60) {
                throw new Exceptions\AuthException('Token is invalid!');
            }

            if (is_callable($callback)) {
                call_user_func_array($callback, [$item]);
            }
        } catch (\Exception $e) {
            throw new Exceptions\AuthException($e->getMessage());
        }

        return true;
    }

    /**
     * Invalidate a token.
     * @param  string $token The user token
     * @param  bool   $isRefresh True if the token is a refresh token
     * @return bool
     */
    public function invalidate(string $token, bool $isRefresh = false): bool
    {
        if (empty($token)) {
            return true;
        }

        return $this->jwt->invalidate($token, $isRefresh);
    }

    /**
     * Get a validator for an incoming login request.
     *
     * @param array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function loginValidator(array $data)
    {
        return Validator::make($data, [
            $this->username() => 'required',
            $this->passwd() => 'required',
        ]);
    }

    /**
     * Get the field credential for check login.
     *
     * @return array
     */
    protected function fieldCredentials(): array
    {
        return $this->config['field_credentials'] ?? [];
    }

    /**
     * Get the field column for get info check login.
     *
     * @return array
     */
    protected function tokenPayloadFields(): array
    {
        return $this->config['token_payload_fields'] ?? ['id'];
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    protected function username(): string
    {
        return $this->config['login_username'] ?? '';
    }

    /**
     * Get the login password to be used by the controller.
     *
     * @return string
     */
    protected function passwd(): string
    {
        return $this->config['login_password'] ?? '';
    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getFailedLoginMessage(): string
    {
        return Lang::has('auth.failed')
            ? Lang::get('auth.failed')
            : 'These credentials do not match our records.';
    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getEmailInvalidMessage(string $email = null): string
    {
        return Lang::has('validation.email')
            ? Lang::get('validation.email', ['attribute' => $email])
            : 'The email is invalid.';
    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getInvalidConfigurationMessage(): string
    {
        return Lang::has('auth.invalid_configuration')
            ? Lang::get('auth.invalid_configuration')
            : 'Invalid auth configuration';
    }
}
