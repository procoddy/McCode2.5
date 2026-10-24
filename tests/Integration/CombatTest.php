<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Contracts\RandomInterface;
use Tests\Fakes\FixedRandom;

final class CombatTest extends IntegrationTestCase
{
    private FixedRandom $random;
    private int $alice;
    private int $bob;
    private int $weapon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->random = new FixedRandom();
        $this->app->container()->instance(RandomInterface::class, $this->random);

        $this->weapon = $this->db->insert('items', ['name' => 'IT Cannon', 'weapon' => 1000]);
        $this->alice = $this->createUser('it_alice', ['energy' => 12, 'max_energy' => 12, 'equipped_primary_id' => $this->weapon, 'level' => 3, 'money' => 0]);
        $this->bob = $this->createUser('it_bob', ['money' => 10000, 'level' => 2]);
        $this->loginAs('it_alice');
    }

    public function test_win_and_mug(): void
    {
        $this->assertSame(200, $this->get("/attack/{$this->bob}")->status());

        $this->random->push(1, 10000, 1); // hit, variance, no crit: 1500 damage
        $response = $this->post("/attack/{$this->bob}", ['weapon_id' => (string) $this->weapon], ['HTTP_HX_REQUEST' => 'true']);
        $this->assertSame(200, $response->status());
        $this->assertStringContainsString('Mug them', $response->content());

        $alice = $this->row('SELECT energy, attacking_user_id FROM users WHERE id = ?', [$this->alice]);
        $this->assertSame(6, (int) $alice['energy'], 'half max energy');
        $this->assertSame($this->bob, (int) $alice['attacking_user_id']);
        $this->assertSame(0, (int) $this->row('SELECT hp FROM users WHERE id = ?', [$this->bob])['hp']);

        $this->random->push(1000, 20); // steal money/100, 20 minutes
        $this->post("/attack/{$this->bob}/finish", ['choice' => 'mug']);

        $bob = $this->row('SELECT hp, money, hospital_until, hospital_reason FROM users WHERE id = ?', [$this->bob]);
        $this->assertSame(1, (int) $bob['hp']);
        $this->assertSame(9900, (int) $bob['money']);
        $this->assertSame('Mugged by it_alice', $bob['hospital_reason']);
        $this->assertNotNull($bob['hospital_until']);
        $alice = $this->row('SELECT money, attacking_user_id FROM users WHERE id = ?', [$this->alice]);
        $this->assertSame(100, (int) $alice['money']);
        $this->assertNull($alice['attacking_user_id']);
        $this->assertNotNull($this->row("SELECT id FROM attack_logs WHERE attacker_id = ? AND outcome = 'mugged' AND money_stolen = 100", [$this->alice]));

        // The same win can't be cashed in again.
        $this->assertSame(403, $this->post("/attack/{$this->bob}/finish", ['choice' => 'mug'])->status());
    }

    public function test_losing_costs_exp_and_ends_fight(): void
    {
        $this->db->update('items', ['weapon' => 1], ['id' => $this->weapon]);
        $this->db->update('user_stats', ['strength' => 100000], ['user_id' => $this->bob]);
        $this->db->update('users', ['exp' => 50], ['id' => $this->alice]);

        $this->random->push(1, 10000, 1, 1, 10000, 1); // alice hits for 1; bob's fists do 101
        $this->post("/attack/{$this->bob}", ['weapon_id' => (string) $this->weapon]);

        $alice = $this->row('SELECT hp, exp, attacking_user_id FROM users WHERE id = ?', [$this->alice]);
        $this->assertSame(0, (int) $alice['hp']);
        $this->assertSame(49, (int) $alice['exp'], '|3 - 2|^3 = 1 EXP lost');
        $this->assertNull($alice['attacking_user_id']);
        $this->assertSame(99, (int) $this->row('SELECT hp FROM users WHERE id = ?', [$this->bob])['hp']);
        $this->assertNotNull($this->row("SELECT id FROM events WHERE user_id = ? AND body = 'it_alice attacked you and lost.'", [$this->bob]));
    }

    public function test_blocked_attacks(): void
    {
        $city = $this->db->insert('cities', ['name' => 'IT Far']);
        $this->db->update('users', ['city_id' => $city], ['id' => $this->bob]);
        $this->post("/attack/{$this->bob}", ['weapon_id' => (string) $this->weapon], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(12, (int) $this->row('SELECT energy FROM users WHERE id = ?', [$this->alice])['energy'], 'different city');

        $this->db->update('users', ['city_id' => 1, 'energy' => 5], ['id' => $this->bob]);
        $this->db->update('users', ['energy' => 5], ['id' => $this->alice]);
        $response = $this->post("/attack/{$this->bob}", ['weapon_id' => (string) $this->weapon], ['HTTP_HX_REQUEST' => 'true']);
        $this->assertSame(422, $response->status());
        $this->assertStringContainsString('50% energy', $response->content());
    }

    public function test_gang_war_respect_and_challenge_bot_bounty(): void
    {
        $now = date('Y-m-d H:i:s');
        $gangA = $this->db->insert('gangs', ['name' => 'IT A', 'respect' => 100, 'created_at' => $now]);
        $gangB = $this->db->insert('gangs', ['name' => 'IT B', 'respect' => 3, 'created_at' => $now]);
        $this->db->insert('gang_wars', ['declarer_gang_id' => $gangA, 'declared_gang_id' => $gangB, 'created_at' => $now]);
        $this->db->update('users', ['gang_id' => $gangA], ['id' => $this->alice]);
        $this->db->update('users', ['gang_id' => $gangB, 'account_type' => 'NPC'], ['id' => $this->bob]);
        $this->db->insert('challenge_bots', ['user_id' => $this->bob, 'reward_money' => 777]);

        $this->assertSame(200, $this->get('/battle-tent')->status());
        $this->random->push(1, 10000, 1, 50, 60);
        $this->post("/attack/{$this->bob}", ['weapon_id' => (string) $this->weapon]);
        $this->post("/attack/{$this->bob}/finish", ['choice' => 'hospitalize']);

        $this->assertSame(103, (int) $this->row('SELECT respect FROM gangs WHERE id = ?', [$gangA])['respect']);
        $this->assertNull($this->row('SELECT id FROM gangs WHERE id = ?', [$gangB]), 'gang B ran out of respect');
        $this->assertNull($this->row('SELECT gang_id FROM users WHERE id = ?', [$this->bob])['gang_id']);
        $this->assertSame(777, (int) $this->row('SELECT money FROM users WHERE id = ?', [$this->alice])['money']);
        $this->assertNotNull($this->row('SELECT 1 FROM challenge_bot_wins WHERE user_id = ? AND bot_user_id = ?', [$this->alice, $this->bob]));
    }

    public function test_lists_and_staff_bots(): void
    {
        $this->post('/enemies/add', ['user_id' => (string) $this->bob, 'comment' => 'Mugged me']);
        $entry = $this->row("SELECT id, comment FROM user_lists WHERE owner_id = ? AND list = 'enemy'", [$this->alice]);
        $this->assertSame('Mugged me', $entry['comment']);
        $this->assertSame(200, $this->get('/enemies')->status());
        $this->post("/enemies/{$entry['id']}/comment", ['comment' => 'Revenge']);
        $this->assertSame('Revenge', $this->row('SELECT comment FROM user_lists WHERE id = ?', [$entry['id']])['comment']);
        $this->post("/friends/add", ['user_id' => (string) $this->bob]);
        $this->assertSame(200, $this->get('/friends')->status());
        $this->post("/enemies/{$entry['id']}/remove");
        $this->assertNull($this->row('SELECT id FROM user_lists WHERE id = ?', [$entry['id']]));

        $this->createUser('it_owner', ['role' => 'admin']);
        $this->loginAs('it_owner');
        $this->db->update('users', ['account_type' => 'NPC'], ['id' => $this->bob]);
        $this->assertSame(200, $this->get('/admin/challenge-bots')->status());
        $this->post('/admin/challenge-bots', ['user_id' => (string) $this->alice, 'reward_money' => '5'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertNull($this->row('SELECT 1 FROM challenge_bots WHERE user_id = ?', [$this->alice]), 'not an NPC');
        $this->post('/admin/challenge-bots', ['user_id' => (string) $this->bob, 'reward_money' => '50']);
        $this->post("/admin/challenge-bots/{$this->bob}", ['_method' => 'PUT', 'reward_money' => '75']);
        $this->assertSame(75, (int) $this->row('SELECT reward_money r FROM challenge_bots WHERE user_id = ?', [$this->bob])['r']);
    }

    public function test_level_up_on_next_page(): void
    {
        $this->db->update('users', ['level' => 1, 'exp' => 20, 'max_hp' => 100, 'hp' => 100], ['id' => $this->alice]);
        $this->get('/dashboard');

        $alice = $this->row('SELECT level, exp, max_hp FROM users WHERE id = ?', [$this->alice]);
        $this->assertSame(2, (int) $alice['level'], '8 * 2.2 = 17 EXP for level 2');
        $this->assertSame(3, (int) $alice['exp']);
        $this->assertSame(150, (int) $alice['max_hp']);
    }
}
