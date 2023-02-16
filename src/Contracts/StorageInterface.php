<?php

namespace SunAsterisk\Auth\Contracts;

interface StorageInterface
{
    /**
     * [add]
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     * @return void
     */
    public function add(string $key, mixed $value, int $minutes): void;

    /**
     * [get]
     * @param  string $key
     * @return [mixed]
     */
    public function get(string $key): mixed;

    /**
     * [has]
     * @param  string  $key
     * @return boolean
     */
    public function has(string $key): bool;

    /**
     * [destroy]
     * @param  string $key
     * @return [bool]
     */
    public function destroy(string $key): bool;
}
