<?php

declare(strict_types=1);

namespace Tests\Integration;

final class PlayerPagesTest extends IntegrationTestCase
{
    public function test_directory_pages_and_profile_render(): void
    {
        $id = $this->createUser('it_alice');
        $this->createUser('it_bob');
        $this->loginAs('it_bob');

        foreach (['/players', '/players?sort=money&dir=desc', '/players/online', '/staff-list', '/stats', "/players/{$id}", "/players/{$id}/report", '/events'] as $page) {
            $this->assertSame(200, $this->get($page)->status(), $page);
        }

        $this->assertStringContainsString('it_bob', $this->get('/players/online')->content(), 'last seen tracked');
        $this->assertSame(404, $this->get('/players/999999999')->status());
    }

    public function test_report_reaches_staff(): void
    {
        $id = $this->createUser('it_alice');
        $this->createUser('it_bob');
        $this->loginAs('it_bob');

        $this->post("/players/{$id}/report", ['body' => 'Spamming the chat all day']);

        $this->assertNotNull($this->row('SELECT id FROM player_reports WHERE reported_id = ? AND body = ?', [$id, 'Spamming the chat all day']));
    }

    public function test_bank_transfer_moves_money_logs_and_notifies(): void
    {
        $alice = $this->createUser('it_alice', ['bank_balance' => 1000]);
        $bob = $this->createUser('it_bob', ['bank_balance' => 0]);
        $carol = $this->createUser('it_carol');
        $this->loginAs('it_alice');

        $this->post("/players/{$bob}/send/bank", ['amount' => '400']);

        $this->assertSame(600, (int) $this->row('SELECT bank_balance b FROM users WHERE id = ?', [$alice])['b']);
        $this->assertSame(400, (int) $this->row('SELECT bank_balance b FROM users WHERE id = ?', [$bob])['b']);
        $this->assertNotNull($this->row("SELECT id FROM transfer_logs WHERE type = 'bank' AND from_user_id = ? AND to_user_id = ? AND amount = 400", [$alice, $bob]));
        $this->assertNotNull($this->row("SELECT id FROM events WHERE user_id = ? AND body LIKE 'You received $400%'", [$bob]));

        // Too much, and receiver without an account: nothing changes.
        $this->post("/players/{$bob}/send/bank", ['amount' => '601'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->post("/players/{$carol}/send/bank", ['amount' => '10'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(600, (int) $this->row('SELECT bank_balance b FROM users WHERE id = ?', [$alice])['b']);
        $this->assertNull($this->row('SELECT bank_balance b FROM users WHERE id = ?', [$carol])['b']);
    }
}
