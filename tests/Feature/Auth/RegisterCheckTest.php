<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class RegisterCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $users = new InMemoryAuthRepository();
        $users->add('taken_name', 'whatever-pass');
        $this->app->container()->instance(AuthRepositoryInterface::class, $users);
    }

    /** @return iterable<string, array{array<string, string>, string}> */
    public static function cases(): iterable
    {
        yield 'free username' => [['username' => 'new_player'], 'Available.'];
        yield 'taken username' => [['username' => 'taken_name'], 'Already taken.'];
        yield 'short username' => [['username' => 'ab'], 'The username must be at least 3 characters.'];
        yield 'bad characters' => [['username' => 'bad name!'], 'The username format is invalid.'];
        yield 'free email' => [['email' => 'New@Example.test'], 'Available.'];
        yield 'taken email' => [['email' => 'Taken_name@example.test'], 'Already registered.'];
        yield 'bad email' => [['email' => 'nope'], 'The email must be a valid email address.'];
    }

    #[DataProvider('cases')]
    public function test_availability(array $input, string $expected): void
    {
        $response = $this->post('/register/check', $input, ['HTTP_HX_REQUEST' => 'true', 'HTTP_X_LAYOUT' => 'guest']);

        $this->assertSame(200, $response->status());
        $this->assertStringContainsString(htmlspecialchars($expected, ENT_QUOTES), $response->content());
    }

    public function test_register_form_wires_live_checks(): void
    {
        $html = $this->get('/register')->content();

        $this->assertStringContainsString('hx-post="/register/check"', $html);
        $this->assertStringContainsString('hx-params="username"', $html);
        $this->assertStringContainsString('id="email-availability"', $html);
    }
}
