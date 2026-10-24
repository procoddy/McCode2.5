<?php

declare(strict_types=1);

namespace Tests\Feature\Game;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use Tests\Feature\Auth\InMemoryAuthRepository;
use Tests\TestCase;

/**
 * Logged in as player 1 ("rocky"); set their stats with $this->players->add(1, [...]).
 */
abstract class GameTestCase extends TestCase
{
    protected const HTMX = ['HTTP_HX_REQUEST' => 'true', 'HTTP_X_LAYOUT' => 'app'];

    protected function setUp(): void
    {
        parent::setUp();

        $users = new InMemoryAuthRepository();
        $users->add('rocky', 'correct-horse');
        $this->app->container()->instance(AuthRepositoryInterface::class, $users);

        $this->post('/login', ['login' => 'rocky', 'password' => 'correct-horse']);
    }

    protected function player(): \App\Modules\Players\Models\Player
    {
        return $this->players->find(1);
    }
}
