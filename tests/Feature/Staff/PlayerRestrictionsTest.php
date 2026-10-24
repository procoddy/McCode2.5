<?php

declare(strict_types=1);

namespace Tests\Feature\Staff;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Models\User;
use App\Support\Money;
use Tests\Feature\Auth\InMemoryAuthRepository;
use Tests\TestCase;

final class PlayerRestrictionsTest extends TestCase
{
    private InMemoryAuthRepository $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryAuthRepository();
        $this->users->add('rocky', 'correct-horse');
        $this->app->container()->instance(AuthRepositoryInterface::class, $this->users);
        $this->post('/login', ['login' => 'rocky', 'password' => 'correct-horse']);
    }

    public function test_federally_jailed_player_only_sees_the_notice(): void
    {
        $this->players->federalJail[1] = ['until' => '2099-01-01 00:00:00', 'reason' => 'Multi accounts'];

        $response = $this->get('/dashboard');

        $this->assertSame(403, $response->status());
        $this->assertStringContainsString('You are in federal jail', $response->content());
        $this->assertStringContainsString('Multi accounts', $response->content());
        $this->assertSame('/login', $this->post('/logout')->getHeader('Location'), 'can still log out');
    }

    public function test_expired_sentence_no_longer_applies(): void
    {
        $this->players->federalJail[1] = ['until' => '2000-01-01 00:00:00', 'reason' => 'Old'];

        $this->assertSame(200, $this->get('/dashboard')->status());
    }

    public function test_force_logout_ends_the_session_once(): void
    {
        $rocky = $this->users->users[1];
        $this->users->users[1] = new User($rocky->id, $rocky->username, $rocky->email, $rocky->passwordHash, 'player', 1, Money::of(100), forceLogout: true);

        $this->assertSame('/login', $this->get('/dashboard')->getHeader('Location'));
    }

    public function test_events_page_marks_read_and_triggers_badge_refresh(): void
    {
        $this->events->add(1, 'You were credited $100.', '2026-10-25 10:00:00');

        $response = $this->get('/events', ['HTTP_HX_REQUEST' => 'true', 'HTTP_X_LAYOUT' => 'app']);

        $this->assertStringContainsString('You were credited $100.', $response->content());
        $this->assertSame('events-read', $response->getHeader('HX-Trigger'));
        $this->assertSame(0, $this->events->countUnread(1));
    }
}
