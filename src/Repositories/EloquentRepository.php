<?php

namespace SunAsterisk\Auth\Repositories;

use Illuminate\Database\Eloquent\Model;
use SunAsterisk\Auth\Contracts\RepositoryInterface;

final class EloquentRepository implements RepositoryInterface
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->model, $method], $parameters);
    }

    public function create(array $param = [])
    {
        return $this->model->create($param);
    }

    public function updateById(int $id, array $param = [])
    {
        return $this->model->whereId($id)->update($param);
    }

    public function findById(int $id)
    {
        return $this->model->find($id);
    }

    public function findByAttribute(array $attribute = [])
    {
        return $this->model->where($attribute)->first();
    }

    public function findByCredentials(array $credentials = [], array $conditions = [], array $columns = ['*'])
    {
        return $this->model
            ->select($columns)
            ->where(function ($query) use ($credentials) {
                foreach ($credentials as $key => $value) {
                    $query->orWhere($key, $value);
                }
            })->where($conditions)->first();
    }
}
