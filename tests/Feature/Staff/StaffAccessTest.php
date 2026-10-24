<?php

declare(strict_types=1);

namespace Tests\Feature\Staff;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use Tests\Feature\Auth\InMemoryAuthRepository;
use Tests\TestCase;

final class StaffAccessTest extends TestCase
{
    private InMemoryAuthRepository $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryAuthRepository();
        $this->users->add('player', 'correct-horse');           // id 1
        $this->users->add('helper', 'correct-horse');           // id 2
        $this->users->add('owner', 'correct-horse', 'admin');   // id 3
        $this->app->container()->instance(AuthRepositoryInterface::class, $this->users);

        $helper = $this->staffRoles->addRole('Helper', ['manage_polls', 'view_logs']);
        $this->staffRoles->grant(2, $helper);
    }

    public function test_players_cannot_enter_the_staff_panel(): void
    {
        $this->login('player');

        $this->assertSame(403, $this->get('/admin')->status());
    }

    public function test_staff_see_only_permitted_menu_links(): void
    {
        $this->login('helper');

        $html = $this->get('/admin')->content();

        $this->assertStringContainsString('href="/admin/logs/staff"', $html);
        $this->assertStringNotContainsString('href="/admin/roles"', $html);
        $this->assertStringNotContainsString('href="/admin/data/items"', $html);
        $this->assertStringNotContainsString('href="/admin/settings"', $html);
        $this->assertStringNotContainsString('System', $html, 'system info is admin-only');
        $this->assertStringContainsString('Hello staff', $html, 'notepad visible to all staff');
    }

    public function test_permission_checks_on_routes(): void
    {
        $this->login('helper');

        $this->assertSame(403, $this->get('/admin/roles')->status());
        $this->assertSame(403, $this->get('/admin/settings')->status());
        $this->assertSame(403, $this->get('/admin/data/items')->status());
        $this->assertSame(403, $this->get('/admin/users')->status());
    }

    public function test_owner_sees_everything(): void
    {
        $this->login('owner');

        // The staff home shows MySQL system info (covered by tests/Integration); check the menu on settings.
        $response = $this->get('/admin/settings');
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('href="/admin/roles"', $response->content());
        $this->assertStringContainsString('href="/admin/data/items"', $response->content());
    }

    public function test_top_bar_staff_link_follows_roles(): void
    {
        $this->login('helper');
        $this->assertStringContainsString('href="/admin"', $this->get('/dashboard')->content());
    }

    public function test_unknown_game_data_type_is_404(): void
    {
        $this->login('owner');

        $this->assertSame(404, $this->get('/admin/data/nope')->status());
    }

    private function login(string $username): void
    {
        $this->post('/login', ['login' => $username, 'password' => 'correct-horse']);
    }
}
