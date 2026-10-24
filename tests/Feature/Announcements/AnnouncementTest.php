<?php

declare(strict_types=1);

namespace Tests\Feature\Announcements;

use App\Modules\Announcements\Contracts\AnnouncementRepositoryInterface;
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use Tests\Feature\Auth\InMemoryAuthRepository;
use Tests\TestCase;

final class AnnouncementTest extends TestCase
{
    private const HTMX_FROM_APP = ['HTTP_HX_REQUEST' => 'true', 'HTTP_X_LAYOUT' => 'app'];

    private const HTMX_FROM_ADMIN = ['HTTP_HX_REQUEST' => 'true', 'HTTP_X_LAYOUT' => 'admin'];

    private InMemoryAnnouncementRepository $announcements;

    private InMemoryAuthRepository $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->users = new InMemoryAuthRepository();
        $this->users->add('rocky', 'correct-horse');
        $this->users->add('boss', 'correct-horse', 'admin');
        $this->announcements = new InMemoryAnnouncementRepository();
        $this->announcements->create(1, 'Old news', '2026-01-01 10:00:00');
        $this->announcements->create(1, "Server <b>restart</b>\nat noon", '2026-01-02 10:00:00');

        $this->app->container()->instance(AuthRepositoryInterface::class, $this->users);
        $this->app->container()->instance(AnnouncementRepositoryInterface::class, $this->announcements);
    }

    public function test_feed_lists_newest_first_escaped_and_flags_unread(): void
    {
        $this->login('rocky');
        $this->announcements->markRead(1, '2026-01-01 12:00:00');

        $html = $this->get('/announcements')->content();

        $this->assertLessThan(strpos($html, 'Old news'), strpos($html, 'Server'));
        $this->assertStringContainsString('Server &lt;b&gt;restart&lt;/b&gt;<br />', $html);
        $this->assertStringNotContainsString('<b>restart</b>', $html);
        $this->assertSame(1, substr_count($html, '>New</span>'));
    }

    public function test_viewing_marks_read_and_tells_the_badge_to_refresh(): void
    {
        $this->login('rocky');
        $this->assertMatchesRegularExpression('/>\s*2<span class="sr-only">/', $this->get('/announcements/unread-count', self::HTMX_FROM_APP)->content());

        $response = $this->get('/announcements', self::HTMX_FROM_APP);

        $this->assertSame('announcements-read', $response->getHeader('HX-Trigger'));
        $this->assertSame('', trim($this->get('/announcements/unread-count', self::HTMX_FROM_APP)->content()));
        $this->assertNull($this->get('/announcements', self::HTMX_FROM_APP)->getHeader('HX-Trigger'));
    }

    public function test_staff_post_via_htmx_returns_updated_panel(): void
    {
        $this->login('boss');

        $response = $this->post('/admin/announcements', ['body' => '  Double XP  '], self::HTMX_FROM_ADMIN);

        $this->assertSame(200, $response->status());
        $this->assertStringStartsWith('<section id="announcements-panel"', trim($response->content()));
        $this->assertStringContainsString('Announcement posted.', $response->content());
        $this->assertStringContainsString('Double XP', $response->content());
        $this->assertSame('Double XP', end($this->announcements->announcements)->body);
        $this->assertSame(3, $this->announcements->countUnread(1));
    }

    public function test_staff_post_with_empty_body_returns_422_panel_with_error(): void
    {
        $this->login('boss');

        $response = $this->post('/admin/announcements', ['body' => '   '], self::HTMX_FROM_ADMIN);

        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('The body field is required.', $response->content());
        $this->assertCount(2, $this->announcements->announcements);
    }

    public function test_staff_post_without_htmx_redirects(): void
    {
        $this->login('boss');

        $response = $this->post('/admin/announcements', ['body' => 'Plain form']);

        $this->assertSame('/admin/announcements', $response->getHeader('Location'));
    }

    public function test_players_cannot_post(): void
    {
        $this->login('rocky');

        $this->assertSame(403, $this->post('/admin/announcements', ['body' => 'Hi'])->status());
        $this->assertCount(2, $this->announcements->announcements);
    }

    private function login(string $username): void
    {
        $this->post('/login', ['login' => $username, 'password' => 'correct-horse']);
    }
}
