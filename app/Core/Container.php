<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ApplicationException;
use Closure;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

final class Container
{
    /** @var array<string, array{concrete: Closure|string, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false): void
    {
        unset($this->instances[$abstract]);
        $this->bindings[$abstract] = ['concrete' => $concrete ?? $abstract, 'shared' => $shared];
    }

    public function singleton(string $abstract, Closure|string|null $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function has(string $abstract): bool
    {
        return isset($this->instances[$abstract]) || isset($this->bindings[$abstract]);
    }

    public function make(string $abstract): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;

        $object = match (true) {
            $concrete instanceof Closure => $concrete($this),
            $concrete === $abstract => $this->build($concrete),
            default => $this->make($concrete),
        };

        if ($this->bindings[$abstract]['shared'] ?? false) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Call a closure or [object|class, method] pair, injecting dependencies.
     * Values in $given are matched to parameters by name.
     */
    public function call(callable|array $callable, array $given = []): mixed
    {
        if (is_array($callable)) {
            [$target, $method] = $callable;
            $target = is_string($target) ? $this->make($target) : $target;
            $reflection = new ReflectionMethod($target, $method);

            return $reflection->invokeArgs($target, $this->resolveParameters($reflection, $given));
        }

        $reflection = new ReflectionFunction(Closure::fromCallable($callable));

        return $reflection->invokeArgs($this->resolveParameters($reflection, $given));
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new ApplicationException("Cannot resolve [{$class}]: class does not exist.");
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new ApplicationException("Cannot resolve [{$class}]: it is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        return $reflection->newInstanceArgs($this->resolveParameters($constructor, []));
    }

    private function resolveParameters(ReflectionFunctionAbstract $function, array $given): array
    {
        return array_map(
            fn (ReflectionParameter $parameter) => $this->resolveParameter($parameter, $given),
            $function->getParameters(),
        );
    }

    private function resolveParameter(ReflectionParameter $parameter, array $given): mixed
    {
        $name = $parameter->getName();
        $type = $parameter->getType();

        if (array_key_exists($name, $given)) {
            return $given[$name];
        }

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            return $this->make($type->getName());
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        $owner = $parameter->getDeclaringClass()?->getName() ?? 'closure';

        throw new ApplicationException("Cannot resolve parameter \${$name} of {$owner}.");
    }
}
