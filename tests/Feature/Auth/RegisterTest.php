<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    private InMemoryAuthRepository $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryAuthRepository();
        $this->app->container()->instance(AuthRepositoryInterface::class, $this->users);
    }

    public function test_register_page_renders(): void
    {
        $response = $this->get('/register');

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('name="password_confirmation"', $response->content());
    }

    public function test_player_can_register_and_is_logged_in(): void
    {
        $response = $this->post('/register', [
            'username' => 'new_player',
            'email' => ' New@Example.TEST ',
            'password' => 'long-enough',
            'password_confirmation' => 'long-enough',
        ]);

        $this->assertSame('/dashboard', $response->getHeader('Location'));

        $user = $this->users->findByLogin('new_player');
        $this->assertNotNull($user);
        $this->assertSame('new@example.test', $user->email);
        $this->assertTrue(password_verify('long-enough', $user->passwordHash));

        $dashboard = $this->get('/dashboard')->content();
        $this->assertStringContainsString('Welcome back, new_player', $dashboard);
        $this->assertStringContainsString('Welcome to the game, new_player!', $dashboard);
    }

    public function test_registered_player_can_log_in_later(): void
    {
        $this->post('/register', [
            'username' => 'new_player',
            'email' => 'new@example.test',
            'password' => 'long-enough',
            'password_confirmation' => 'long-enough',
        ]);
        $this->post('/logout');

        $response = $this->post('/login', ['login' => 'new@example.test', 'password' => 'long-enough']);

        $this->assertSame('/dashboard', $response->getHeader('Location'));
    }

    public function test_duplicate_username_and_email_are_rejected(): void
    {
        $this->users->add('taken', 'whatever-pass');

        $this->post('/register', [
            'username' => 'taken',
            'email' => 'taken@example.test',
            'password' => 'long-enough',
            'password_confirmation' => 'long-enough',
        ], ['HTTP_REFERER' => 'http://localhost/register']);

        $this->assertSame(['username', 'email'], array_keys($_SESSION['_errors']));
        $this->assertCount(1, $this->users->users);
    }

    public function test_invalid_input_is_rejected(): void
    {
        $response = $this->post('/register', [
            'username' => 'bad name!',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ], ['HTTP_REFERER' => 'http://localhost/register']);

        $this->assertSame('/register', $response->getHeader('Location'));
        $this->assertSame(
            'Usernames may only contain letters, numbers and underscores.',
            $_SESSION['_errors']['username'][0],
        );
        $this->assertArrayHasKey('email', $_SESSION['_errors']);
        $this->assertArrayHasKey('password', $_SESSION['_errors']);
        $this->assertArrayNotHasKey('password', $_SESSION['_old_input']);
        $this->assertSame([], $this->users->users);
    }

    public function test_mismatched_confirmation_is_rejected(): void
    {
        $this->post('/register', [
            'username' => 'new_player',
            'email' => 'new@example.test',
            'password' => 'long-enough',
            'password_confirmation' => 'different!',
        ]);

        $this->assertSame('The two passwords do not match.', $_SESSION['_errors']['password'][0]);

        // Only the field with the error is marked invalid.
        $html = $this->get('/register')->content();
        $this->assertStringContainsString('id="field-password-error">The two passwords do not match.', $html);
        $this->assertStringNotContainsString('field-password_confirmation-error', $html);
        $this->assertStringNotContainsString('field-email-error', $html);
    }

    public function test_logged_in_players_are_redirected_away(): void
    {
        $this->users->add('rocky', 'correct-horse');
        $this->post('/login', ['login' => 'rocky', 'password' => 'correct-horse']);

        $this->assertSame('/dashboard', $this->get('/register')->getHeader('Location'));
    }
}
