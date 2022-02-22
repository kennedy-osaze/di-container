<?php

namespace KennedyOsaze\Container;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class DependencyResolver
{
    protected Container $container;

    private array $dependencies;

    private array $parameters;

    /**
     * Create an instance of the resolver class
     *
     * @param \KennedyOsaze\Container\Container $container
     *
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the dependencies of the constructor that needs to be resolved with the parameters needed by the constructor
     *
     * @param \ReflectionMethod $constructor
     * @param array $parameters
     *
     * @return self
     */
    public function using(ReflectionMethod $constructor, array $parameters = []): self
    {
        $this->dependencies = $constructor->getParameters();

        $this->parameters = $this->rebuildParameters($parameters);

        return $this;
    }

    /**
     * Normalize the parameters array using the dependencies available
     *
     * @param array $parameters
     *
     * @return array
     */
    private function rebuildParameters(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameters[$key]);

                $parameters[$this->dependencies[$key]->name] = $value;
            }
        }

        return $parameters;
    }

    /**
     * Resolve and retrieve all dependencies
     *
     * @return array
     */
    public function getDependencies()
    {
        $results = [];

        foreach ($this->dependencies as $dependency) {
            $class = $this->getParameterClass($dependency);

            if (array_key_exists($dependency->name, $this->parameters)) {
                $results[] = $this->parameters[$dependency->name];
            } elseif (is_null($class)) {
                $results[] = $this->resolveNonClass($dependency);
            } else {
                $results[] = $this->resolveClass($class, $dependency);
            }
        }

        return $results;
    }

    /**
     * Get the associated class of a dependency parameter
     *
     * @param \ReflectionParameter $parameter
     *
     * @return string|null
     */
    private function getParameterClass(ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();
        $class = $parameter->getDeclaringClass();

        return (is_null($class) || ! in_array($name, ['self', 'static'])) ? $name : $class->getName();
    }

    /**
     * Resolve a non-class dependency
     *
     * @param \ReflectionParameter $dependency
     *
     * @return mixed
     *
     * @throws \KennedyOsaze\Container\ContainerException
     */
    private function resolveNonClass(ReflectionParameter $dependency)
    {
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        throw new ContainerException(vsprintf("Could not resolve the dependency [%s] in the class [%s]", [
            $dependency, $dependency->getDeclaringClass()->getName()
        ]));
    }

    /**
     * Resolve a class dependency
     *
     * @param string $class
     *
     * @param \ReflectionParameter $dependency
     *
     * @return mixed
     *
     * @throws \KennedyOsaze\Container\ContainerException
     */
    private function resolveClass(string $class, ReflectionParameter $dependency)
    {
        try {
            return $this->container->resolve($class);
        } catch (ContainerException $e) {
            if ($dependency->isOptional()) {
                return $dependency->getDefaultValue();
            }

            throw $e;
        }
    }
}
