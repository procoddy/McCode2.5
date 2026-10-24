<?php

declare(strict_types=1);

namespace Tests\Integration;

final class StaffPanelTest extends IntegrationTestCase
{
    private int $playerId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createUser('it_owner', ['role' => 'admin']);
        $this->playerId = $this->createUser('it_player');
        $this->loginAs('it_owner');
    }

    public function test_every_staff_page_renders(): void
    {
        $pages = ['/admin', '/admin/settings', '/admin/cron', '/admin/announcements', '/admin/roles', '/admin/roles/create',
            '/admin/roles/members', '/admin/users', "/admin/users/{$this->playerId}/edit", '/admin/users/credit',
            '/admin/users/mass-credit', '/admin/reports', '/admin/fedjail'];
        foreach (array_keys(config('staff_resources')) as $key) {
            $pages[] = "/admin/data/{$key}";
            $pages[] = "/admin/data/{$key}/create";
        }
        foreach (array_keys(\App\Modules\Staff\Support\StaffLogViews::all()) as $key) {
            $pages[] = "/admin/logs/{$key}";
        }

        foreach ($pages as $page) {
            $this->assertSame(200, $this->get($page)->status(), $page);
        }
    }

    public function test_city_crud_with_unique_name_and_protected_default(): void
    {
        $this->post('/admin/data/cities', ['name' => 'Gotham', 'description' => 'Dark', 'min_level' => '5']);
        $city = $this->row('SELECT * FROM cities WHERE name = ?', ['Gotham']);
        $this->assertSame(5, (int) $city['min_level']);

        $this->post('/admin/data/cities', ['name' => 'Gotham', 'min_level' => '1'], ['HTTP_REFERER' => 'http://localhost/admin/data/cities/create']);
        $this->assertArrayHasKey('name', $_SESSION['_errors'], 'duplicate name rejected');

        $this->db->update('users', ['city_id' => $city['id']], ['id' => $this->playerId]);
        $this->post("/admin/data/cities/{$city['id']}", ['_method' => 'DELETE']);
        $this->assertNull($this->row('SELECT id FROM cities WHERE id = ?', [$city['id']]));
        $this->assertSame(1, (int) $this->row('SELECT city_id FROM users WHERE id = ?', [$this->playerId])['city_id'], 'players moved to default city');

        $this->post('/admin/data/cities/1', ['_method' => 'DELETE']);
        $this->assertNotNull($this->row('SELECT id FROM cities WHERE id = 1'), 'default city protected');
    }

    public function test_crime_formula_is_validated_and_groups_in_use_cannot_be_deleted(): void
    {
        $this->post('/admin/data/crime-groups', ['name' => 'IT group', 'sort_order' => '900']);
        $groupId = (int) $this->row('SELECT id FROM crime_groups WHERE name = ?', ['IT group'])['id'];

        $base = ['name' => 'IT crime', 'group_id' => (string) $groupId, 'brave_cost' => '2', 'success_money' => '10',
            'success_crystals' => '0', 'crime_exp' => '1', 'jail_minutes' => '5'];

        $this->post('/admin/data/crimes', [...$base, 'success_formula' => 'system("x")'], ['HTTP_REFERER' => 'http://localhost/admin/data/crimes/create']);
        $this->assertNotEmpty($_SESSION['_errors']['success_formula'] ?? [], 'PHP in a formula is rejected');
        $this->assertNull($this->row('SELECT id FROM crimes WHERE name = ?', ['IT crime']));

        $this->post('/admin/data/crimes', [...$base, 'success_formula' => 'WILL / 2']);
        $this->assertNotNull($this->row('SELECT id FROM crimes WHERE name = ?', ['IT crime']));

        $this->post("/admin/data/crime-groups/{$groupId}", ['_method' => 'DELETE']);
        $this->assertNotNull($this->row('SELECT id FROM crime_groups WHERE id = ?', [$groupId]), 'group with crimes kept');
    }

    public function test_shop_stock_pivot_is_synced(): void
    {
        $knife = $this->db->insert('items', ['name' => 'IT Knife']);
        $vest = $this->db->insert('items', ['name' => 'IT Vest']);

        $this->post('/admin/data/shops', ['name' => 'IT Shop', 'city_id' => '1', 'items' => ['', (string) $knife, (string) $vest]]);
        $shopId = (int) $this->row('SELECT id FROM shops WHERE name = ?', ['IT Shop'])['id'];
        $this->assertSame(2, (int) $this->row('SELECT COUNT(*) n FROM shop_items WHERE shop_id = ?', [$shopId])['n']);

        $this->post("/admin/data/shops/{$shopId}", ['_method' => 'PUT', 'name' => 'IT Shop', 'city_id' => '1', 'items' => ['', (string) $vest]]);
        $this->assertSame([$vest], array_map('intval', array_column($this->db->fetchAll('SELECT item_id FROM shop_items WHERE shop_id = ?', [$shopId]), 'item_id')));
    }

    public function test_roles_grant_access(): void
    {
        $this->post('/admin/roles', ['name' => 'IT Logs', 'permissions' => ['view_logs']]);
        $roleId = (int) $this->row('SELECT id FROM staff_roles WHERE name = ?', ['IT Logs'])['id'];
        $this->post('/admin/roles/grant', ['username' => 'it_player', 'role' => (string) $roleId]);

        $this->loginAs('it_player');
        $this->assertSame(200, $this->get('/admin/logs/staff')->status());
        $this->assertSame(403, $this->get('/admin/users')->status());
    }

    public function test_edit_credit_and_staff_log(): void
    {
        $this->post('/admin/users/credit', ['username' => 'it_player', 'money' => '500', 'crystals' => '3']);
        $player = $this->row('SELECT money, crystals FROM users WHERE id = ?', [$this->playerId]);
        $this->assertSame([600, 3], [(int) $player['money'], (int) $player['crystals']]);
        $this->assertNotNull($this->row("SELECT id FROM events WHERE user_id = ? AND body LIKE 'You were credited%'", [$this->playerId]));
        $this->assertNotNull($this->row("SELECT id FROM staff_logs WHERE action LIKE 'Credited it_player%'"));

        $edit = $this->get("/admin/users/{$this->playerId}/edit")->content();
        $this->assertStringContainsString('value="it_player"', $edit);
    }

    public function test_federal_jail_blocks_the_player_until_released(): void
    {
        $this->post('/admin/fedjail', ['username' => 'it_player', 'days' => '2', 'reason' => 'Testing']);
        $this->assertNotNull($this->row('SELECT user_id FROM federal_jail WHERE user_id = ?', [$this->playerId]));

        $this->loginAs('it_player');
        $this->assertSame(403, $this->get('/dashboard')->status());

        $this->loginAs('it_owner');
        $this->post("/admin/fedjail/{$this->playerId}/release");
        $this->loginAs('it_player');
        $this->assertSame(200, $this->get('/dashboard')->status());
    }
}
