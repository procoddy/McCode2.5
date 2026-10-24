<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    private InMemoryAuthRepository $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryAuthRepository();
        $this->app->container()->instance(AuthRepositoryInterface::class, $this->users);
    }

    public function test_login_page_renders_with_security_headers(): void
    {
        $response = $this->get('/login');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('name="_token"', $response->content());
        $this->assertSame('DENY', $response->getHeader('X-Frame-Options'));
    }

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $this->assertSame(302, $response->status());
        $this->assertSame('/login', $response->getHeader('Location'));
    }

    public function test_user_can_log_in_and_reach_dashboard(): void
    {
        $this->users->add('rocky', 'correct-horse');

        $response = $this->post('/login', ['login' => 'rocky', 'password' => 'correct-horse']);

        $this->assertSame('/dashboard', $response->getHeader('Location'));
        $this->assertCount(1, $this->users->logins);
        $this->assertStringContainsString('Welcome back, rocky', $this->get('/dashboard')->content());
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->users->add('rocky', 'correct-horse');

        $response = $this->post('/login', ['login' => 'rocky', 'password' => 'wrong'], ['HTTP_REFERER' => 'http://localhost/login']);

        $this->assertSame('/login', $response->getHeader('Location'));
        $this->assertArrayHasKey('login', $_SESSION['_errors']);
        $this->assertSame('rocky', $_SESSION['_old_input']['login']);
        $this->assertArrayNotHasKey('password', $_SESSION['_old_input']);
    }

    public function test_post_without_csrf_token_is_rejected(): void
    {
        $response = $this->app->handle(\App\Core\Request::create('POST', '/login', ['login' => 'a', 'password' => 'b']));

        $this->assertSame(419, $response->status());
    }

    public function test_players_cannot_open_staff_panel(): void
    {
        $this->users->add('rocky', 'correct-horse');
        $this->post('/login', ['login' => 'rocky', 'password' => 'correct-horse']);

        $this->assertSame(403, $this->get('/admin')->status());
    }

    public function test_logout_ends_session(): void
    {
        $this->users->add('rocky', 'correct-horse');
        $this->post('/login', ['login' => 'rocky', 'password' => 'correct-horse']);

        $response = $this->post('/logout');

        $this->assertSame('/login', $response->getHeader('Location'));
        $this->assertSame('You have been logged out.', $_SESSION['status']);
        $this->assertSame(302, $this->get('/dashboard')->status());
    }

    public function test_unknown_route_returns_404(): void
    {
        $this->assertSame(404, $this->get('/does-not-exist')->status());
    }
}
