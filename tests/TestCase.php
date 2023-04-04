<?php

namespace SunAsterisk\Auth\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param $obj
     * @param $name
     * @param array $args
     * @return mixed
     * @throws \ReflectionException
     */
    protected function callMethodProtectedOrPrivate($obj, $name, array $args)
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    protected function setAttributeProtectedOrPrivate($obj, string $propertyName, $value)
    {
        $reflectionClass = new \ReflectionClass($obj);

        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
        $property->setAccessible(false);
    }

    protected function callAttributeProtectedOrPrivate($obj, string $propertyName)
    {
        $reflectionClass = new \ReflectionClass($obj);

        $property = $reflectionClass->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($obj);
    }
}
