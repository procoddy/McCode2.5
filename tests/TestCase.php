<?php

declare(strict_types=1);

namespace Tests;

use App\Contracts\MiddlewareInterface;
use App\Core\Application;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Http\Middleware\RateLimitMiddleware;
use App\Modules\Events\Contracts\EventRepositoryInterface;
use App\Modules\Players\Contracts\PlayerRepositoryInterface;
use App\Modules\Settings\Contracts\SettingsRepositoryInterface;
use App\Modules\Staff\Contracts\StaffRoleRepositoryInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\Fakes\InMemoryEventRepository;
use Tests\Fakes\InMemoryPlayerRepository;
use Tests\Fakes\InMemorySettingsRepository;
use Tests\Fakes\InMemoryStaffRoleRepository;

/**
 * Boots the real application and dispatches requests in-process.
 */
abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected InMemoryPlayerRepository $players;

    protected InMemoryStaffRoleRepository $staffRoles;

    protected InMemoryEventRepository $events;

    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
        $this->app = new Application(dirname(__DIR__));

        // Feature tests never touch MySQL; tests/Integration covers the real SQL.
        if ($this->usesFakes()) {
            $this->players = new InMemoryPlayerRepository();
            $this->staffRoles = new InMemoryStaffRoleRepository();
            $this->events = new InMemoryEventRepository();
            $this->app->container()->instance(PlayerRepositoryInterface::class, $this->players);
            $this->app->container()->instance(StaffRoleRepositoryInterface::class, $this->staffRoles);
            $this->app->container()->instance(EventRepositoryInterface::class, $this->events);
            $this->app->container()->instance(SettingsRepositoryInterface::class, new InMemorySettingsRepository());
            // Any query that slips past the fakes fails loudly instead of hitting MySQL.
            $this->app->container()->instance(Database::class, new Database(['driver' => 'none']));
        }

        $this->app->container()->bind(RateLimitMiddleware::class, fn () => new class implements MiddlewareInterface {
            public function handle(Request $request, callable $next, string ...$parameters): Response
            {
                return $next($request);
            }
        });
    }

    protected function usesFakes(): bool
    {
        return true;
    }

    protected function get(string $uri, array $headers = []): Response
    {
        return $this->app->handle(Request::create('GET', $uri, [], $headers));
    }

    protected function post(string $uri, array $data = [], array $headers = []): Response
    {
        $token = $this->app->container()->make(Session::class)->token();

        return $this->app->handle(Request::create('POST', $uri, ['_token' => $token, ...$data], $headers));
    }
}
