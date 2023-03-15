<?php

namespace SunAsterisk\Auth\Services;

use SunAsterisk\Auth\Contracts;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Auth\StatefulGuard;
use InvalidArgumentException;

final class AuthSessionService implements Contracts\AuthSessionInterface
{
    /**
     * The repository implementation.
     *
     * @var \SunAsterisk\Auth\Contracts\RepositoryInterface
     */
    protected $repository;

    /**
     * The guard implementation.
     *
     * @var \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected $guard;

    /**
     * The config implementation.
     *
     * @var array
     */
    private array $config = [];

    public function __construct(Contracts\RepositoryInterface $repository, StatefulGuard $guard, array $config = [])
    {
        $this->repository = $repository;
        $this->guard = $guard;
        $this->config = $config;
    }

    /**
     * [login]
     * @param  array         $credentials [The user's attributes for authentication.]
     * @param  array|null    $attributes  [The attributes use when query.]
     * @param  callable|null $callback    [The callback function has the entity model.]
     * @return [bool]
     */
    public function login(array $credentials = [], ?array $attributes = [], ?callable $callback = null): bool
    {
        $this->loginValidator($credentials)->validate();
        $hasRemember = $credentials['remember'] ?? false;
        if (empty($attributes)) {
            $attributes = Arr::only($credentials, $this->username());
        }
        $item = $this->repository->findByAttribute($attributes);

        if (! $item || ! Hash::check(Arr::get($credentials, $this->passwd()), $item->{$this->passwd()})) {
            throw ValidationException::withMessages([
                $this->username() => $this->getFailedLoginMessage(),
            ]);
        }

        if (is_callable($callback)) {
            call_user_func_array($callback, [$item]);
        }

        $this->guard->login($item, $hasRemember);

        return true;
    }

    /**
     * [logout]
     * @param  Illuminate\Http\Request $request [Request from controller]
     * @return [void]
     */
    public function logout(Request $request): void
    {
        $this->guard->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
    }

    /**
     * [register]
     * @param  array         $fields    [The user's attributes for register.]
     * @param  array         $rules     [The rules for register validate.]
     * @param  callable|null $callback  [The callback function has the entity model.]
     * @param  bool $setGuard  [The setGuard allow authenticated after register.]
     * @return [array]
     */
    public function register(
        array $params = [],
        array $rules = [],
        callable $callback = null,
        bool $setGuard = false
    ): bool {
        $table = $this->repository->getTable();
        if (empty($rules)) {
            $rules = [
                $this->username() => ['required', 'string', "unique:{$table}," . $this->username()],
                $this->passwd() => [
                    'required',
                    'min:6',
                    'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!@#$%]).*$/',
                ],
            ];

            if (isset($params['email'])) {
                $rules['email'] = ['required', 'email', "unique:{$table},email"];
            }
        }

        Validator::make($params, $rules)->validate();

        $params[$this->passwd()] = Hash::make($params[$this->passwd()]);

        $item = $this->repository->create($params);

        if (is_callable($callback)) {
            $itemArr = call_user_func_array($callback, [$item]);
        }

        if ($setGuard) {
            $this->guard->login($item);
        }

        return true;
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
}
