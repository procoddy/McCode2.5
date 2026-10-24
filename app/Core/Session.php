<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public function __construct(private readonly array $config)
    {
        $_SESSION ??= [];
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!empty($this->config['path']) && is_dir($this->config['path'])) {
            session_save_path($this->config['path']);
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', (string) (($this->config['lifetime'] ?? 120) * 60));

        session_name($this->config['name'] ?? 'mccode_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => (bool) ($this->config['secure'] ?? false),
            'httponly' => true,
            'samesite' => $this->config['same_site'] ?? 'Lax',
        ]);
        session_start();

        $this->ageFlashData();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);

        return $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Store a value that is available on the next request only.
     */
    public function flash(string $key, mixed $value): void
    {
        $this->put($key, $value);
        $_SESSION['_flash']['new'][] = $key;
    }

    /**
     * Keep the current flash data for one more request (used when a response
     * immediately sends the browser elsewhere and nothing has displayed it).
     */
    public function reflash(): void
    {
        $_SESSION['_flash']['new'] = array_values(array_unique([
            ...($_SESSION['_flash']['new'] ?? []),
            ...($_SESSION['_flash']['old'] ?? []),
        ]));
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function invalidate(): void
    {
        $_SESSION = [];
        $this->regenerate();
    }

    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->regenerateToken();
        }

        return $this->get('_token');
    }

    public function regenerateToken(): void
    {
        $this->put('_token', bin2hex(random_bytes(32)));
    }

    private function ageFlashData(): void
    {
        foreach ($_SESSION['_flash']['old'] ?? [] as $key) {
            if (!in_array($key, $_SESSION['_flash']['new'] ?? [], true)) {
                unset($_SESSION[$key]);
            }
        }

        $_SESSION['_flash'] = ['old' => $_SESSION['_flash']['new'] ?? [], 'new' => []];
    }
}
