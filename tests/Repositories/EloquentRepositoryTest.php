<?php

namespace SunAsterisk\Auth\Tests\Repositories;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Mockery;

/**
 * @covers \SunAsterisk\Auth\Repositories\EloquentRepository
 */
final class EloquentRepositoryTest extends TestCase
{
    protected $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = Mockery::mock(Model::class)->makePartial();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_provider_asks_model_to_find()
    {
        $eloquent = new EloquentRepository($this->model);
        $expected = (object) [];
        $this->model->shouldReceive('find')->with(1)->once()->andReturn($expected);
        $actual = $eloquent->find(1);

        $this->assertEquals($actual, $expected);
    }

    public function test_create()
    {
        $eloquent = new EloquentRepository($this->model);
        $expected = (object) [];
        $this->model->shouldReceive('create')->with([])->once()->andReturn($expected);
        $actual = $eloquent->create([]);

        $this->assertEquals($actual, $expected);
    }

    public function test_update_by_id()
    {
        $eloquent = new EloquentRepository($this->model);
        $this->model->shouldReceive('whereId')->with(1)->once()->andReturn($this->model);
        $this->model->shouldReceive('update')->with([])->once()->andReturn(true);
        $actual = $eloquent->updateById(1, []);

        $this->assertTrue($actual);
    }

    public function test_find_by_id()
    {
        $eloquent = new EloquentRepository($this->model);
        $expected = (object) [];
        $this->model->shouldReceive('find')->with(1)->once()->andReturn($expected);
        $actual = $eloquent->findById(1);

        $this->assertEquals($actual, $expected);
    }

    public function test_find_by_attribute()
    {
        $eloquent = new EloquentRepository($this->model);
        $expected = (object) [];
        $this->model->shouldReceive('where')->with([])->once()->andReturn($this->model);
        $this->model->shouldReceive('first')->once()->andReturn($expected);
        $actual = $eloquent->findByAttribute([]);

        $this->assertEquals($actual, $expected);
    }

    public function test_find_by_credentials()
    {
        $eloquent = new EloquentRepository($this->model);
        $expected = (object) [];
        $this->model->shouldReceive('where')->with(Mockery::on(function ($closure) {
            $builder = Mockery::mock(Builder::class)->makePartial();
            $builder->shouldReceive('orWhere')->with('key', 'value')->once()->andReturn($builder);
            $closure($builder);

            return true;
        }))->once()->andReturn($this->model);
        $this->model->shouldReceive('select')->andReturn($this->model);
        $this->model->shouldReceive('where')->with([])->once()->andReturn($this->model);
        $this->model->shouldReceive('first')->once()->andReturn($expected);
        $actual = $eloquent->findByCredentials(['key' => 'value'], [], ['id', 'email', 'password']);

        $this->assertEquals($actual, $expected);
    }
}
