<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\MiddlewareInterface;
use App\Exceptions\ApplicationException;
use App\Exceptions\NotFoundException;
use App\Http\Requests\BaseRequest;
use Closure;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

final class Router
{
    /** @var list<array{method: string, uri: string, pattern: string, action: Closure|array, middleware: list<string>}> */
    private array $routes = [];

    private array $groupStack = [];

    private array $globalMiddleware = [];

    private array $aliases = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function setGlobalMiddleware(array $middleware): void
    {
        $this->globalMiddleware = $middleware;
    }

    public function setAliases(array $aliases): void
    {
        $this->aliases = $aliases;
    }

    public function get(string $uri, Closure|array $action, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, Closure|array $action, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, Closure|array $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function patch(string $uri, Closure|array $action, array $middleware = []): void
    {
        $this->addRoute('PATCH', $uri, $action, $middleware);
    }

    public function delete(string $uri, Closure|array $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    /**
     * Register the seven CRUD routes for a controller:
     * index, create, store, show, edit, update, destroy.
     */
    public function resource(string $uri, string $controller, array $middleware = []): void
    {
        $uri = '/' . trim($uri, '/');

        $this->get($uri, [$controller, 'index'], $middleware);
        $this->get("{$uri}/create", [$controller, 'create'], $middleware);
        $this->post($uri, [$controller, 'store'], $middleware);
        $this->get("{$uri}/{id}", [$controller, 'show'], $middleware);
        $this->get("{$uri}/{id}/edit", [$controller, 'edit'], $middleware);
        $this->put("{$uri}/{id}", [$controller, 'update'], $middleware);
        $this->delete("{$uri}/{id}", [$controller, 'destroy'], $middleware);
    }

    /**
     * @param array{prefix?: string, middleware?: list<string>} $attributes
     */
    public function group(array $attributes, callable $routes): void
    {
        $this->groupStack[] = $attributes;
        $routes($this);
        array_pop($this->groupStack);
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function dispatch(Request $request): Response
    {
        $core = fn (Request $request): Response => $this->runRoute($request);

        return $this->pipeline($this->globalMiddleware, $core)($request);
    }

    private function addRoute(string $method, string $uri, Closure|array $action, array $middleware): void
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach ($this->groupStack as $group) {
            $prefix .= '/' . trim($group['prefix'] ?? '', '/');
            $groupMiddleware = [...$groupMiddleware, ...($group['middleware'] ?? [])];
        }

        $uri = '/' . trim(preg_replace('#/+#', '/', $prefix . '/' . trim($uri, '/')), '/');
        $pattern = '#^' . preg_replace('/\{(\w+)}/', '(?P<$1>[^/]+)', $uri) . '$#';

        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'pattern' => $pattern,
            'action' => $action,
            'middleware' => [...$groupMiddleware, ...$middleware],
        ];
    }

    private function runRoute(Request $request): Response
    {
        $method = $request->method() === 'HEAD' ? 'GET' : $request->method();
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path(), $matches)) {
                continue;
            }

            $pathMatched = true;

            if ($route['method'] !== $method) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $core = fn (Request $request): Response => $this->callAction($route['action'], $request, $params);

            return $this->pipeline($route['middleware'], $core)($request);
        }

        if ($pathMatched) {
            throw new ApplicationException('Method Not Allowed', 405);
        }

        throw new NotFoundException();
    }

    /**
     * @param list<string> $middleware
     */
    private function pipeline(array $middleware, Closure $core): Closure
    {
        return array_reduce(
            array_reverse($middleware),
            function (Closure $next, string $definition): Closure {
                [$name, $args] = array_pad(explode(':', $definition, 2), 2, null);
                $args = $args === null ? [] : explode(',', $args);

                return function (Request $request) use ($name, $args, $next): Response {
                    $middleware = $this->container->make($this->aliases[$name] ?? $name);

                    if (!$middleware instanceof MiddlewareInterface) {
                        throw new ApplicationException("Middleware [{$name}] must implement MiddlewareInterface.");
                    }

                    return $middleware->handle($request, $next, ...$args);
                };
            },
            $core,
        );
    }

    private function callAction(Closure|array $action, Request $request, array $params): Response
    {
        $reflection = $action instanceof Closure
            ? new ReflectionFunction($action)
            : new ReflectionMethod($action[0], $action[1]);

        $given = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();
            $name = $parameter->getName();

            if (!$type instanceof ReflectionNamedType) {
                continue;
            }

            if ($type->isBuiltin() && array_key_exists($name, $params)) {
                $given[$name] = match ($type->getName()) {
                    'int' => filter_var($params[$name], FILTER_VALIDATE_INT) !== false
                        ? (int) $params[$name]
                        : throw new NotFoundException(),
                    default => $params[$name],
                };
            } elseif (is_subclass_of($type->getName(), BaseRequest::class)) {
                $formRequest = $this->container->make($type->getName());
                $formRequest->validateResolved();
                $given[$name] = $formRequest;
            }
        }

        return Response::from($this->container->call($action, $given));
    }
}
