<?php

namespace SunAsterisk\Auth\Tests\Providers;

use SunAsterisk\Auth\Tests\TestCase;
use SunAsterisk\Auth\Providers\Storage;
use Illuminate\Contracts\Cache\Repository;
use Mockery;

/**
 * @covers \SunAsterisk\Auth\Providers\Storage
 */
final class StorageTest extends TestCase
{
    protected $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = Mockery::mock(Repository::class)->makePartial();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_add()
    {
        $storage = new Storage($this->cache);
        $this->cache->shouldReceive('put')->with('key', 'value', 1)->once();
        $storage->add('key', 'value', 1);

        $this->assertTrue(true);
    }

    public function test_get()
    {
        $storage = new Storage($this->cache);
        $this->cache->shouldReceive('get')->with('key')->once()->andReturn(true);
        $actual = $storage->get('key');

        $this->assertTrue($actual);
    }

    public function test_has()
    {
        $storage = new Storage($this->cache);
        $this->cache->shouldReceive('has')->with('key')->once()->andReturn(true);
        $actual = $storage->has('key');

        $this->assertTrue($actual);
    }

    public function test_destroy()
    {
        $storage = new Storage($this->cache);
        $this->cache->shouldReceive('forget')->with('key')->once()->andReturn(true);
        $actual = $storage->destroy('key');

        $this->assertTrue($actual);
    }
}
