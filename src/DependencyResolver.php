<?php

namespace KennedyOsaze\Container;

use ReflectionMethod;
use ReflectionParameter;

class DependencyResolver
{
    protected Container $container;

    private array $dependencies;

    private array $parameters;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function using(ReflectionMethod $constructor, array $parameters)
    {
        $this->dependencies = $constructor->getParameters();

        $this->parameters = $this->rebuildParameters($parameters);

        return $this;
    }

    private function rebuildParameters(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (is_numeric($key)) {
                unset($parameter[$key]);
            }

            $parameter[$this->dependencies[$key]->name] = $value;
        }

        return $parameters;
    }

    public function getDependencies()
    {
        $results = [];

        foreach ($this->dependencies as $dependency) {

            if (array_key_exists($dependency->name, $this->parameters)) {
                $results[] = $this->parameters[$dependency->name];
            } elseif (is_null($dependency->getDeclaringClass())) {
                $results[] = $this->resolveNonClass($dependency);
            } else {
                $results[] = $this->resolveClass($dependency);
            }
        }

        return $results;
    }

    private function resolveNonClass(ReflectionParameter $dependency)
    {
        if ($dependency->isDefaultValueAvailable()) {
            return $dependency->getDefaultValue();
        }

        throw new ContainerException(vsprintf("Could not resolve the dependency [%s] in the class [%s]", [
            $dependency, $dependency->getDeclaringClass()->getName()
        ]));
    }

    private function resolveClass(ReflectionParameter $dependency)
    {
        try {
            return $this->container->resolve($dependency->getClass()->name);
        } catch (ContainerException $e) {
            if ($dependency->isOptional()) {
                return $dependency->getDefaultValue();
            }

            throw $e;
        }
    }
}
