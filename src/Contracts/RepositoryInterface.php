<?php

namespace SunAsterisk\Auth\Contracts;

interface RepositoryInterface
{
    /**
     * [create] Insert from params to database
     * @param  array  $param
     * @return [entity]
     */
    public function create(array $param = []);

    /**
     * [updateById]
     * @param  int $id
     * @param  array  $param
     * @return [bool]
     */
    public function updateById(int $id, array $param = []);
    /**
     * [findById] get object by id
     * @param  int    $id
     * @return [entity]
     */
    public function findById(int $id);

    /**
     * [findByAttribute]
     * @param  [array] $attribute
     * @return [entity]
     */
    public function findByAttribute(array $attribute = []);

    /**
     * [findByCredentials]
     * @param  array  $credentials
     * @param  array  $conditions
     * @return [entity]
     */
    public function findByCredentials(array $credentials = [], array $conditions = [], array $columns = ['id']);
}
