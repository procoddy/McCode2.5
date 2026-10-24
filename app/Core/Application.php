<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\ClockInterface;
use App\Contracts\LoggerInterface;
use App\Contracts\RandomInterface;
use App\Modules\Announcements\Contracts\AnnouncementRepositoryInterface;
use App\Modules\Announcements\Repositories\AnnouncementRepository;
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Repositories\AuthRepository;
use App\Modules\Crimes\Contracts\CrimeRepositoryInterface;
use App\Modules\Crimes\Repositories\CrimeRepository;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Players\Repositories\PlayerRepository;
use App\Modules\Events\Contracts\EventRepositoryInterface;
use App\Modules\Events\Repositories\EventRepository;
use App\Modules\Settings\Contracts\SettingsRepositoryInterface;
use App\Modules\Settings\Repositories\SettingsRepository;
use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;
use App\Modules\Staff\Repositories\StaffRoleRepository;
use App\Modules\Staff\Services\StaffPermissions;
use App\Support\Arr;
use App\Support\SystemClock;
use App\Support\SystemRandom;
use Throwable;

final class Application
{
    private static ?self $instance = null;

    private Container $container;

    private array $config = [];

    private bool $routesLoaded = false;

    public function __construct(private readonly string $basePath)
    {
        self::$instance = $this;

        $this->container = new Container();
        $this->container->instance(self::class, $this);
        $this->container->instance(Container::class, $this->container);

        $this->loadEnvironment();
        $this->loadConfiguration();
        $this->registerServices();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \LogicException('Application has not been created.');
        }

        return self::$instance;
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath . ($path !== '' ? '/' . ltrim($path, '/') : '');
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function config(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->config, $key, $default);
    }

    public function environment(): string
    {
        return (string) $this->config('app.env', 'production');
    }

    public function run(): void
    {
        $request = Request::capture();

        $this->container->make(Session::class)->start();

        $this->handle($request)->send();
    }

    public function handle(Request $request): Response
    {
        $this->container->instance(Request::class, $request);

        $view = $this->container->make(View::class);
        $view->share('appName', $this->config('app.name'));
        $view->share('errors', $this->container->make(Session::class)->get('_errors', []));

        // Redirects are left alone for HTMX: the browser follows them inside the
        // same XHR, and view() decides whether the result can be swapped in.
        try {
            return $this->router()->dispatch($request);
        } catch (Throwable $e) {
            return $this->container->make(ExceptionHandler::class)->render($e, $request);
        }
    }

    public function router(): Router
    {
        $router = $this->container->make(Router::class);

        if (!$this->routesLoaded) {
            $router->setGlobalMiddleware($this->config('app.middleware', []));
            $router->setAliases($this->config('app.middleware_aliases', []));

            foreach ($this->config('app.routes', []) as $file) {
                (require $this->basePath("routes/{$file}.php"))($router);
            }

            $this->routesLoaded = true;
        }

        return $router;
    }

    private function loadEnvironment(): void
    {
        $file = $this->basePath('.env');

        if (!is_file($file)) {
            return;
        }

        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = preg_replace('/^(["\'])(.*)\1$/', '$2', $value);

            // Real environment variables win over .env values.
            if (getenv($key) === false && !isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    private function loadConfiguration(): void
    {
        foreach (glob($this->basePath('config/*.php')) as $file) {
            $this->config[basename($file, '.php')] = require $file;
        }
    }

    private function registerServices(): void
    {
        date_default_timezone_set((string) $this->config('app.timezone', 'UTC'));

        $c = $this->container;

        $c->singleton(LoggerInterface::class, fn () => new Logger(
            (string) $this->config('logging.path'),
            (string) $this->config('logging.level', 'warning'),
        ));
        $c->singleton(Database::class, fn () => new Database(
            $this->config('database.connections.' . $this->config('database.default'), []),
        ));
        $c->singleton(Session::class, fn () => new Session($this->config('session', [])));
        $c->singleton(View::class, fn () => new View($this->basePath('resources/views')));
        $c->singleton(Router::class);
        $c->singleton(Validator::class);
        $c->singleton(ExceptionHandler::class, fn (Container $c) => new ExceptionHandler(
            $c->make(LoggerInterface::class),
            $c->make(View::class),
            $c->make(Session::class),
            (bool) $this->config('app.debug', false),
        ));
        $c->singleton(ClockInterface::class, SystemClock::class);
        $c->singleton(RandomInterface::class, SystemRandom::class);

        // Module bindings
        $c->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $c->bind(AnnouncementRepositoryInterface::class, AnnouncementRepository::class);
        $c->bind(PlayerRepositoryInterface::class, PlayerRepository::class);
        $c->bind(CrimeRepositoryInterface::class, CrimeRepository::class);
        $c->bind(EventRepositoryInterface::class, EventRepository::class);
        $c->singleton(SettingsRepositoryInterface::class, SettingsRepository::class);
        $c->bind(StaffRoleRepositoryInterface::class, StaffRoleRepository::class);
        // One per request so permission lookups are cached.
        $c->singleton(StaffPermissions::class);
    }
}
