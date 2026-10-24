<?php

declare(strict_types=1);

namespace Tests\Integration;

final class InventoryTest extends IntegrationTestCase
{
    private int $alice;
    private int $bob;
    private int $sword;
    private int $potion;
    private int $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $type = $this->db->insert('item_types', ['name' => 'IT Weapons']);
        $this->sword = $this->db->insert('items', ['type_id' => $type, 'name' => 'IT Sword', 'buy_price' => 100, 'sell_price' => 40, 'buyable' => 1, 'weapon' => 10]);
        $this->potion = $this->db->insert('items', [
            'name' => 'IT Potion', 'buy_price' => 10, 'sell_price' => 5, 'buyable' => 1,
            'effects' => json_encode([
                ['stat' => 'energy', 'direction' => 'increase', 'type' => 'figure', 'amount' => 1000],
                ['stat' => 'strength', 'direction' => 'increase', 'type' => 'figure', 'amount' => 3],
                ['stat' => 'jail', 'direction' => 'increase', 'type' => 'figure', 'amount' => 30],
            ]),
        ]);
        $this->shop = $this->db->insert('shops', ['city_id' => 1, 'name' => 'IT Shop']);
        $this->db->insert('shop_items', ['shop_id' => $this->shop, 'item_id' => $this->sword]);
        $this->db->insert('shop_items', ['shop_id' => $this->shop, 'item_id' => $this->potion]);

        $this->alice = $this->createUser('it_alice', ['money' => 1000, 'crystals' => 50, 'energy' => 1, 'max_energy' => 12]);
        $this->bob = $this->createUser('it_bob', ['money' => 500]);
        $this->loginAs('it_alice');
    }

    public function test_pages_render(): void
    {
        $this->post("/shops/{$this->shop}/buy/{$this->sword}", ['quantity' => '1']);

        foreach (['/inventory', "/inventory/{$this->sword}", '/shops', "/shops/{$this->shop}", '/item-market', '/crystal-market'] as $page) {
            $this->assertSame(200, $this->get($page)->status(), $page);
        }
    }

    public function test_buy_needs_money_and_same_city(): void
    {
        $this->post("/shops/{$this->shop}/buy/{$this->sword}", ['quantity' => '3']);
        $this->assertSame(700, $this->money($this->alice));
        $this->assertSame(3, $this->quantity($this->alice, $this->sword));
        $this->assertNotNull($this->row("SELECT id FROM item_logs WHERE type = 'shop_buy' AND user_id = ? AND quantity = 3 AND price = 300", [$this->alice]));

        $this->post("/shops/{$this->shop}/buy/{$this->sword}", ['quantity' => '8'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(700, $this->money($this->alice), "can't afford");

        $city = $this->db->insert('cities', ['name' => 'IT Elsewhere']);
        $this->db->update('shops', ['city_id' => $city], ['id' => $this->shop]);
        $this->assertSame(403, $this->post("/shops/{$this->shop}/buy/{$this->sword}", ['quantity' => '1'])->status());
    }

    public function test_use_applies_effects_with_caps(): void
    {
        $this->db->insert('inventory', ['user_id' => $this->alice, 'item_id' => $this->potion, 'quantity' => 2]);

        $this->post("/inventory/{$this->potion}/use");

        $user = $this->row('SELECT energy, jail_until, jail_reason FROM users WHERE id = ?', [$this->alice]);
        $this->assertSame(12, (int) $user['energy'], 'capped at max energy');
        $this->assertNotNull($user['jail_until']);
        $this->assertSame('Used an IT Potion', $user['jail_reason']);
        $this->assertEquals(13, (float) $this->row('SELECT strength FROM user_stats WHERE user_id = ?', [$this->alice])['strength']);
        $this->assertSame(1, $this->quantity($this->alice, $this->potion));

        $this->post("/inventory/{$this->potion}/use");
        $this->post("/inventory/{$this->potion}/use", [], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(0, $this->quantity($this->alice, $this->potion));
        $this->assertEquals(16, (float) $this->row('SELECT strength FROM user_stats WHERE user_id = ?', [$this->alice])['strength'], 'third use had no potion');
    }

    public function test_equip_swaps_and_unequip_returns(): void
    {
        $other = $this->db->insert('items', ['name' => 'IT Axe', 'weapon' => 12]);
        $this->db->insert('inventory', ['user_id' => $this->alice, 'item_id' => $this->sword, 'quantity' => 1]);
        $this->db->insert('inventory', ['user_id' => $this->alice, 'item_id' => $other, 'quantity' => 1]);

        $this->post("/inventory/{$this->sword}/equip", ['slot' => 'primary']);
        $this->assertSame($this->sword, (int) $this->row('SELECT equipped_primary_id e FROM users WHERE id = ?', [$this->alice])['e']);
        $this->assertSame(0, $this->quantity($this->alice, $this->sword));

        $this->post("/inventory/{$other}/equip", ['slot' => 'primary']);
        $this->assertSame($other, (int) $this->row('SELECT equipped_primary_id e FROM users WHERE id = ?', [$this->alice])['e']);
        $this->assertSame(1, $this->quantity($this->alice, $this->sword), 'old weapon back in inventory');

        $this->post("/inventory/{$this->potion}/equip", ['slot' => 'armor'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertNull($this->row('SELECT equipped_armor_id e FROM users WHERE id = ?', [$this->alice])['e'], 'not armor');

        $this->post('/inventory/unequip/primary');
        $this->assertNull($this->row('SELECT equipped_primary_id e FROM users WHERE id = ?', [$this->alice])['e']);
        $this->assertSame(1, $this->quantity($this->alice, $other));
    }

    public function test_sell_and_send(): void
    {
        $this->db->insert('inventory', ['user_id' => $this->alice, 'item_id' => $this->sword, 'quantity' => 5]);

        $this->post("/inventory/{$this->sword}/sell", ['sell_quantity' => '2']);
        $this->assertSame(1080, $this->money($this->alice));

        $this->post("/inventory/{$this->sword}/send", ['send_quantity' => '3', 'user_id' => (string) $this->bob]);
        $this->assertSame(0, $this->quantity($this->alice, $this->sword));
        $this->assertSame(3, $this->quantity($this->bob, $this->sword));
        $this->assertNotNull($this->row("SELECT id FROM events WHERE user_id = ? AND body = 'You received 3 IT Sword from it_alice.'", [$this->bob]));

        $this->post("/inventory/{$this->sword}/sell", ['sell_quantity' => '1'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(1080, $this->money($this->alice), 'none left to sell');
    }

    public function test_htmx_use_returns_panel_with_status_bar(): void
    {
        $this->db->insert('inventory', ['user_id' => $this->alice, 'item_id' => $this->potion, 'quantity' => 1]);

        $response = $this->post("/inventory/{$this->potion}/use", [], ['HTTP_HX_REQUEST' => 'true']);
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('id="inventory-panel"', $response->content());
        $this->assertStringContainsString('hx-swap-oob', $response->content());

        $response = $this->post("/inventory/{$this->potion}/use", [], ['HTTP_HX_REQUEST' => 'true']);
        $this->assertSame(422, $response->status());
    }

    public function test_item_market(): void
    {
        $this->db->insert('inventory', ['user_id' => $this->alice, 'item_id' => $this->sword, 'quantity' => 4]);

        $this->post('/item-market', ['item_id' => (string) $this->sword, 'quantity' => '3', 'price' => '50', 'currency' => 'money']);
        $listing = (int) $this->row('SELECT id FROM item_market_listings WHERE seller_id = ?', [$this->alice])['id'];
        $this->assertSame(1, $this->quantity($this->alice, $this->sword));

        $this->loginAs('it_bob');
        $this->post("/item-market/{$listing}/buy", ['quantity' => '2']);
        $this->assertSame(400, $this->money($this->bob));
        $this->assertSame(1100, $this->money($this->alice));
        $this->assertSame(2, $this->quantity($this->bob, $this->sword));
        $this->assertSame(1, (int) $this->row('SELECT quantity q FROM item_market_listings WHERE id = ?', [$listing])['q']);

        $this->post("/item-market/{$listing}/remove", [], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertNotNull($this->row('SELECT id FROM item_market_listings WHERE id = ?', [$listing]), "bob can't remove alice's listing");

        $this->loginAs('it_alice');
        $this->post("/item-market/{$listing}/remove");
        $this->assertNull($this->row('SELECT id FROM item_market_listings WHERE id = ?', [$listing]));
        $this->assertSame(2, $this->quantity($this->alice, $this->sword));
    }

    public function test_crystal_market(): void
    {
        $this->post('/crystal-market', ['quantity' => '20', 'price_each' => '10']);
        $this->assertSame(30, (int) $this->row('SELECT crystals c FROM users WHERE id = ?', [$this->alice])['c']);
        $listing = (int) $this->row('SELECT id FROM crystal_market_listings WHERE seller_id = ?', [$this->alice])['id'];

        $this->post('/crystal-market', ['quantity' => '31', 'price_each' => '10'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(30, (int) $this->row('SELECT crystals c FROM users WHERE id = ?', [$this->alice])['c'], 'not enough');

        $this->loginAs('it_bob');
        $this->post("/crystal-market/{$listing}/buy", ['quantity' => '20']);
        $this->assertSame(300, $this->money($this->bob));
        $this->assertSame(20, (int) $this->row('SELECT crystals c FROM users WHERE id = ?', [$this->bob])['c']);
        $this->assertSame(1200, $this->money($this->alice));
        $this->assertNull($this->row('SELECT id FROM crystal_market_listings WHERE id = ?', [$listing]), 'sold out');
    }

    public function test_staff_effects_editor_and_give_item(): void
    {
        $this->createUser('it_owner', ['role' => 'admin']);
        $this->loginAs('it_owner');

        $this->post('/admin/data/items', [
            'name' => 'IT Elixir', 'type_id' => '', 'description' => '', 'buy_price' => '5', 'sell_price' => '1', 'buyable' => '1',
            'weapon' => '0', 'armor' => '0',
            'effects' => [
                ['stat' => 'hp', 'direction' => 'increase', 'type' => 'percent', 'amount' => '50'],
                ['stat' => '', 'direction' => 'increase', 'type' => 'figure', 'amount' => ''],
                ['stat' => 'money', 'direction' => 'decrease', 'type' => 'figure', 'amount' => '7'],
            ],
        ]);
        $row = $this->row("SELECT id, effects FROM items WHERE name = 'IT Elixir'");
        // MySQL's JSON type reorders keys, hence assertEquals.
        $this->assertEquals([
            ['stat' => 'hp', 'direction' => 'increase', 'type' => 'percent', 'amount' => 50],
            ['stat' => 'money', 'direction' => 'decrease', 'type' => 'figure', 'amount' => 7],
        ], json_decode($row['effects'], true));
        $this->assertSame(200, $this->get("/admin/data/items/{$row['id']}/edit")->status());
        $this->assertSame(200, $this->get('/admin/users/give-item')->status());

        $this->post('/admin/users/give-item', ['username' => 'it_bob', 'item_id' => (string) $row['id'], 'quantity' => '4']);
        $this->assertSame(4, $this->quantity($this->bob, (int) $row['id']));

        $this->assertSame(200, $this->get("/admin/users/{$this->bob}/inventory")->status());
        $this->post("/admin/users/{$this->bob}/inventory/{$row['id']}/remove", ['quantity' => '3']);
        $this->assertSame(1, $this->quantity($this->bob, (int) $row['id']));
    }

    private function money(int $id): int
    {
        return (int) $this->row('SELECT money FROM users WHERE id = ?', [$id])['money'];
    }

    private function quantity(int $userId, int $itemId): int
    {
        return (int) ($this->row('SELECT quantity FROM inventory WHERE user_id = ? AND item_id = ?', [$userId, $itemId])['quantity'] ?? 0);
    }
}
