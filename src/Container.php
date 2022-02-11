<?php

namespace KennedyOsaze\Container;

use ArrayAccess;
use Closure;
use ReflectionClass;
use ReflectionException;
use TypeError;

class Container implements ArrayAccess
{
    protected array $bindings = [];

    protected array $instances = [];

    public function singleton(string $name, $concrete = null)
    {
        $this->bind($name, $concrete, true);
    }

    public function bind(string $name, $concrete = null, bool $singleton = false)
    {
        unset($this->instances[$name]);

        $concrete = $concrete ?? $name;

        if (! $concrete instanceof Closure) {
            if (! is_string($concrete)) {
                throw new TypeError(static::class.'::bind(): Argument #2 ($concrete) must be of type Closure|string|null');
            }

            $concrete = $this->getClosure($name, $concrete);
        }

        $this->bindings[$name] = compact('concrete', 'singleton');
    }

    protected function getClosure(string $name, $concrete)
    {
        return function (self $container) use ($name, $concrete) {
            return $name === $concrete ? $container->build($concrete) : $container->resolve($name);
        };
    }

    public function instance(string $name, $concrete)
    {
        $this->instance[$name] = $concrete;
    }

    public function has(string $name)
    {
        return isset($this->bindings[$name]) || isset($this->instances[$name]);
    }

    public function get(string $name)
    {
        return $this->resolve($name);
    }

    public function resolve(string $name, array $parameters = [])
    {
        if (isset($this->instances[$name]) && ! empty($parameters)) {
            return $this->instances[$name];
        }

        $instance = $this->createInstance($name, $parameters);

        if ($this->isSingleton($name) && ! empty($parameters)) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    protected function createInstance(string $name, array $parameters = [])
    {
        $concrete = $this->bindings[$name]['concrete'] ?? $name;

        if ($concrete === $name || $concrete instanceof Closure) {
            return $this->build($concrete, $parameters);
        }

        throw new ContainerException("The binding key [$name] does not exists");
    }

    public function build($concrete, array $parameters = [])
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $parameters);
        }

        $reflector = $this->getReflectionInstance($concrete);

        if (! $constructor = $reflector->getConstructor()) {
            return $reflector->newInstance();
        }

        $resolver = $this->getDependencyResolver();
        $dependencies = $resolver->using($constructor, $parameters)->getDependencies();

        return $reflector->newInstanceArgs($dependencies);
    }

    protected function getReflectionInstance($concrete)
    {
        try {
            $reflector = new ReflectionClass($concrete);
        } catch (ReflectionException $e) {
            throw new ContainerException("Target class [{$concrete}] does not exist.", 0, $e);
        }

        if (! $reflector->isInstantiable()) {
            throw new ContainerException("Target class [{$concrete}] cannot be instantiated.");
        }

        return $reflector;
    }

    protected function getDependencyResolver()
    {
        return new DependencyResolver($this);
    }

    public function isSingleton(string $name)
    {
        return isset($this->instances[$name]) || ($this->bindings[$name]['singleton'] ?? false);
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function flush()
    {
        $this->bindings = $this->instances = $this->resolved = [];
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->bind($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset]);
    }
}
