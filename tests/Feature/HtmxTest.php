<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use Tests\Feature\Auth\InMemoryAuthRepository;
use Tests\TestCase;

final class HtmxTest extends TestCase
{
    private const BOOSTED_FROM_APP = [
        'HTTP_HX_REQUEST' => 'true',
        'HTTP_HX_BOOSTED' => 'true',
        'HTTP_HX_TARGET' => 'content',
        'HTTP_X_LAYOUT' => 'app',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $users = new InMemoryAuthRepository();
        $users->add('rocky', 'correct-horse');
        $this->app->container()->instance(AuthRepositoryInterface::class, $users);
    }

    public function test_normal_request_gets_full_page_with_htmx_wiring(): void
    {
        $html = $this->get('/login')->content();

        $this->assertStringContainsString('<!doctype html>', $html);
        $this->assertStringContainsString('hx-boost="true"', $html);
        $this->assertStringContainsString('/assets/js/htmx.min.js', $html);
        $this->assertStringContainsString('<title>Log in · ', $html);
    }

    public function test_boosted_navigation_gets_fragment_and_pushes_url(): void
    {
        $this->login();

        $response = $this->get('/dashboard?tab=1', self::BOOSTED_FROM_APP);

        $this->assertSame(200, $response->status());
        $this->assertStringNotContainsString('<html', $response->content());
        $this->assertStringNotContainsString('<aside', $response->content());
        $this->assertStringContainsString('<title>Dashboard · ', $response->content());
        $this->assertStringContainsString('Welcome back, rocky', $response->content());
        $this->assertSame('/dashboard?tab=1', $response->getHeader('HX-Push-Url'));
    }

    public function test_different_layout_triggers_full_page_load_and_keeps_flash(): void
    {
        $this->login();
        $this->post('/logout');

        // Logging out redirects to /login (guest layout) while the app layout is on screen.
        $response = $this->get('/login', self::BOOSTED_FROM_APP);

        $this->assertSame('/login', $response->getHeader('HX-Redirect'));
        $this->assertSame('', $response->content());

        // The flash message survives the extra hop and shows on the full page.
        $this->assertStringContainsString('You have been logged out.', $this->get('/login')->content());
    }

    public function test_history_restore_gets_full_page(): void
    {
        $this->login();

        $response = $this->get('/dashboard', [...self::BOOSTED_FROM_APP, 'HTTP_HX_HISTORY_RESTORE_REQUEST' => 'true']);

        $this->assertStringContainsString('<!doctype html>', $response->content());
    }

    public function test_partial_endpoint_returns_only_the_block(): void
    {
        $this->login();

        $response = $this->get('/dashboard/stats', ['HTTP_HX_REQUEST' => 'true', 'HTTP_X_LAYOUT' => 'app']);

        $this->assertStringStartsWith('<section id="stats"', trim(preg_replace('/^<\?php.*?\?>/s', '', $response->content())));
        $this->assertStringNotContainsString('<title>', $response->content());
    }

    public function test_htmx_error_pages_are_fragments(): void
    {
        $response = $this->get('/nope', self::BOOSTED_FROM_APP);

        $this->assertSame(404, $response->status());
        $this->assertStringNotContainsString('<html', $response->content());
        $this->assertStringContainsString('Not Found', $response->content());
    }

    public function test_csp_allows_only_local_scripts(): void
    {
        $csp = $this->get('/login')->getHeader('Content-Security-Policy');

        $this->assertStringContainsString("script-src 'self';", $csp);
        $this->assertStringNotContainsString('unpkg', $csp);
    }

    private function login(): void
    {
        $this->post('/login', ['login' => 'rocky', 'password' => 'correct-horse']);
    }
}
