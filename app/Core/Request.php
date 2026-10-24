<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    private array $attributes = [];

    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query = [],
        private readonly array $body = [],
        private readonly array $server = [],
        private readonly array $files = [],
        private readonly array $cookies = [],
    ) {
    }

    public static function capture(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $body = $_POST;

        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $body = json_decode((string) file_get_contents('php://input'), true) ?: [];
        }

        // HTML forms can only send GET/POST; allow a hidden _method override.
        if ($method === 'POST' && in_array(strtoupper($body['_method'] ?? ''), ['PUT', 'PATCH', 'DELETE'], true)) {
            $method = strtoupper($body['_method']);
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

        // Support installs in a sub-directory (e.g. /McCode2.5/public).
        if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }

        return new self($method, self::normalizePath($path), $_GET, $body, $_SERVER, $_FILES, $_COOKIE);
    }

    public static function create(string $method, string $uri, array $parameters = [], array $server = []): self
    {
        $method = strtoupper($method);

        // Same _method override as capture(), so tests exercise PUT/DELETE forms exactly like browsers.
        if ($method === 'POST' && in_array(strtoupper((string) ($parameters['_method'] ?? '')), ['PUT', 'PATCH', 'DELETE'], true)) {
            $method = strtoupper($parameters['_method']);
        }
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        return new self(
            $method,
            self::normalizePath($path),
            $method === 'GET' ? [...$query, ...$parameters] : $query,
            $method === 'GET' ? [] : $parameters,
            [...['REMOTE_ADDR' => '127.0.0.1', 'REQUEST_METHOD' => $method], ...$server],
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return [...$this->query, ...$this->body];
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key, mixed $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = strtoupper(str_replace('-', '_', $name));

        return $this->server['HTTP_' . $key] ?? $this->server[$key] ?? $default;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function referer(): ?string
    {
        return $this->header('Referer');
    }

    /** Path plus query string, e.g. /items?page=2 */
    public function fullPath(): string
    {
        return $this->path . ($this->query === [] ? '' : '?' . http_build_query($this->query));
    }

    public function isHtmx(): bool
    {
        return $this->header('HX-Request') === 'true';
    }

    /** A link or form inside an hx-boost container (page navigation, not a partial update). */
    public function isBoosted(): bool
    {
        return $this->header('HX-Boosted') === 'true';
    }

    /** HTMX asks for the full page when its history cache misses (back/forward). */
    public function isHtmxHistoryRestore(): bool
    {
        return $this->header('HX-History-Restore-Request') === 'true';
    }

    /** True when HTMX expects only the page content, not the whole document. */
    public function wantsFragment(): bool
    {
        return $this->isHtmx() && !$this->isHtmxHistoryRestore();
    }

    public function expectsJson(): bool
    {
        return str_contains($this->header('Accept', ''), 'application/json');
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    private static function normalizePath(string $path): string
    {
        return '/' . trim($path, '/');
    }
}
