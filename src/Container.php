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

    /**
     * Registers a binding as singleton
     *
     * @param string $name
     * @param mixed $concrete
     *
     * @return void
     */
    public function singleton(string $name, $concrete = null): void
    {
        $this->bind($name, $concrete, true);
    }

    /**
     * Registers a binding
     *
     * @param string $name
     * @param mixed $concrete
     * @param bool $singleton
     *
     * @return void
     */
    public function bind(string $name, $concrete = null, bool $singleton = false): void
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

    /**
     * Retrieve the closure wrapper for an implementation
     *
     * @param string $name
     * @param mixed $concrete
     *
     * @return \Closure
     */
    protected function getClosure(string $name, $concrete): Closure
    {
        return function (self $container, array $parameters = []) use ($name, $concrete) {
            return $name == $concrete
                ? $container->build($concrete)
                : $container->resolve($concrete, $parameters);
        };
    }

    /**
     * Registers a value as a singleton
     *
     * @param string $name
     * @param mixed $concrete
     *
     * @return void
     */
    public function instance(string $name, $concrete): void
    {
        $this->instances[$name] = $concrete;
    }

    /**
     * Determines whether the binding with the provided name exists
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->bindings[$name]) || isset($this->instances[$name]);
    }

    /**
     * Get the implementation for the binding provided by the name
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->resolve($name);
    }

    /**
     * Attempts to resolve the binding
     *
     * @param string $name
     * @param array $parameters
     *
     * @return mixed
     *
     * @throws \KennedyOsaze\Container\ContainerException;
     */
    public function resolve(string $name, array $parameters = [])
    {
        if (isset($this->instances[$name]) && empty($parameters)) {
            return $this->instances[$name];
        }

        $instance = $this->createInstance($name, $parameters);

        if ($this->isSingleton($name) && empty($parameters)) {
            $this->instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * Create an instance of the binding provided by the given name
     *
     * @param string $name
     * @param array $parameters
     *
     * @return mixed
     *
     * @throws \KennedyOsaze\Container\ContainerException;
     */
    protected function createInstance(string $name, array $parameters = [])
    {
        $concrete = $this->bindings[$name]['concrete'] ?? $name;

        if ($concrete === $name || $concrete instanceof Closure) {
            return $this->build($concrete, $parameters);
        }

        throw new ContainerException("The binding key [$name] does not exists");
    }

    /**
     * Builds the concrete implementation and its dependencies
     *
     * @param mixed $concrete
     * @param array $parameters
     *
     * @return mixed
     *
     * @throws \KennedyOsaze\Container\ContainerException;
     */
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

    /**
     * Get a reflection class that represents the concrete implementation
     *
     * @param mixed $concrete
     *
     * @return \ReflectionClass
     *
     * @throws \KennedyOsaze\Container\ContainerException;
     */
    protected function getReflectionInstance($concrete): ReflectionClass
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

    /**
     * Get the dependency resolver
     *
     * @return \KennedyOsaze\Container\DependencyResolver
     */
    protected function getDependencyResolver(): DependencyResolver
    {
        return new DependencyResolver($this);
    }

    /**
     * Determine whether a concrete was bound as a singleton
     *
     * @param string $name
     *
     * @return bool
     */
    public function isSingleton(string $name): bool
    {
        return isset($this->instances[$name]) || ($this->bindings[$name]['singleton'] ?? false);
    }

    /**
     * Get all bindings registered in the container
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Clear up all bindings and instances registered in the container
     *
     * @return void
     */
    public function flush()
    {
        $this->bindings = $this->instances = [];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value): void
    {
        $this->bind($offset, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset): void
    {
        unset($this->bindings[$offset], $this->instances[$offset]);
    }
}
