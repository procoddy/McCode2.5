<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Contracts\RandomInterface;
use Tests\Fakes\FixedRandom;

final class GangTest extends IntegrationTestCase
{
    private int $boss;
    private int $joe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->boss = $this->createUser('it_boss', ['money' => 600000, 'crystals' => 10]);
        $this->joe = $this->createUser('it_joe', ['money' => 1000]);
        $this->loginAs('it_boss');
        $this->post('/gangs/create', ['name' => 'IT Gang', 'description' => 'Test']);
    }

    private function gang(): array
    {
        return $this->row("SELECT * FROM gangs WHERE name = 'IT Gang'");
    }

    public function test_create_costs_money_and_makes_president(): void
    {
        $gang = $this->gang();
        $this->assertSame($this->boss, (int) $gang['president_id']);
        $this->assertSame(100000, (int) $this->row('SELECT money FROM users WHERE id = ?', [$this->boss])['money']);
        $this->assertSame((int) $gang['id'], (int) $this->row('SELECT gang_id FROM users WHERE id = ?', [$this->boss])['gang_id']);

        foreach (['/gang', '/gang/members', '/gang/wars', '/gang/attack-logs', '/gang/crimes', '/gang/staff', '/gangs', "/gangs/{$gang['id']}"] as $page) {
            $this->assertSame(200, $this->get($page)->status(), $page);
        }

        $this->loginAs('it_joe');
        $this->post('/gangs/create', ['name' => 'Poor Gang'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertNull($this->row("SELECT id FROM gangs WHERE name = 'Poor Gang'"), "can't afford");
        $this->assertSame(404, $this->get('/gang/staff')->status(), 'not in a gang');
    }

    public function test_apply_accept_donate_vault_kick(): void
    {
        $gangId = (int) $this->gang()['id'];

        $this->loginAs('it_joe');
        $this->post("/gangs/{$gangId}/apply", ['body' => 'Let me in']);
        $this->assertSame(404, $this->get('/gang')->status(), 'not a member yet');

        $this->loginAs('it_boss');
        $app = (int) $this->row('SELECT id FROM gang_applications WHERE user_id = ?', [$this->joe])['id'];
        $this->post("/gang/staff/applications/{$app}/accept");
        $this->assertSame($gangId, (int) $this->row('SELECT gang_id FROM users WHERE id = ?', [$this->joe])['gang_id']);

        $this->loginAs('it_joe');
        $this->assertSame(403, $this->post('/gang/staff/upgrade', ['slots' => '1'])->status(), 'members are not leaders');
        $this->post('/gang/donate', ['money' => '400', 'crystals' => '0']);
        $this->assertSame(400, (int) $this->gang()['money']);

        $this->loginAs('it_boss');
        $this->post('/gang/staff/vault', ['user_id' => (string) $this->joe, 'money' => '150', 'crystals' => '0']);
        $this->assertSame(250, (int) $this->gang()['money']);
        $this->assertSame(750, (int) $this->row('SELECT money FROM users WHERE id = ?', [$this->joe])['money']);
        $this->post('/gang/staff/vault', ['user_id' => (string) $this->joe, 'money' => '999', 'crystals' => '0'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(250, (int) $this->gang()['money'], 'vault too small');

        $this->post('/gang/staff/mass-payment', ['amount' => '100']);
        $this->assertSame(50, (int) $this->gang()['money']);

        $this->post('/gang/staff/mass-mail', ['subject' => 'Meeting', 'body' => 'Tonight']);
        $this->assertNotNull($this->row("SELECT id FROM mail WHERE recipient_id = ? AND subject = 'Meeting'", [$this->joe]));

        $this->post("/gang/members/{$this->joe}/kick");
        $this->assertNull($this->row('SELECT gang_id FROM users WHERE id = ?', [$this->joe])['gang_id']);
    }

    public function test_president_cannot_leave_and_war_and_surrender(): void
    {
        $gangId = (int) $this->gang()['id'];
        $this->post('/gang/leave', [], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame($gangId, (int) $this->row('SELECT gang_id FROM users WHERE id = ?', [$this->boss])['gang_id']);

        $this->db->update('users', ['money' => 600000], ['id' => $this->joe]);
        $this->loginAs('it_joe');
        $this->post('/gangs/create', ['name' => 'IT Rivals']);
        $rivals = (int) $this->row("SELECT id FROM gangs WHERE name = 'IT Rivals'")['id'];

        $this->loginAs('it_boss');
        $this->post('/gang/staff/war', ['gang_id' => (string) $rivals]);
        $war = (int) $this->row('SELECT id FROM gang_wars WHERE declarer_gang_id = ? AND declared_gang_id = ?', [$gangId, $rivals])['id'];

        $this->loginAs('it_joe');
        $this->post('/gang/staff/surrender', ['war_id' => (string) $war, 'message' => 'Mercy']);
        $surrender = (int) $this->row('SELECT id FROM gang_war_surrenders WHERE war_id = ?', [$war])['id'];

        $this->loginAs('it_boss');
        $this->post("/gang/staff/surrenders/{$surrender}/accept");
        $this->assertNull($this->row('SELECT id FROM gang_wars WHERE id = ?', [$war]));
    }

    public function test_upgrade_and_organised_crime_via_cron(): void
    {
        $gangId = (int) $this->gang()['id'];
        $this->db->update('gangs', ['money' => 250000], ['id' => $gangId]);
        $this->post('/gang/staff/upgrade', ['slots' => '2']);
        $gang = $this->gang();
        $this->assertSame(7, (int) $gang['capacity']);
        $this->assertSame(50000, (int) $gang['money']);

        $crime = $this->db->insert('organised_crimes', ['name' => 'IT Heist', 'members_required' => 1, 'min_money' => 1000, 'max_money' => 2000, 'success_text' => 'Got {muny}']);
        $this->post('/gang/staff/crime', ['crime_id' => (string) $crime]);
        $this->assertSame($crime, (int) $this->gang()['organised_crime_id']);

        $this->db->update('gangs', ['crime_ready_at' => date('Y-m-d H:i:s', time() - 60)], ['id' => $gangId]);
        $random = new FixedRandom([1, 1500]);
        $this->app->container()->instance(RandomInterface::class, $random);
        $this->app->container()->make(\App\Console\CronJobs::class)->run('hourly');

        $gang = $this->gang();
        $this->assertNull($gang['organised_crime_id']);
        $this->assertSame(51500, (int) $gang['money']);
        $this->assertSame('Got $1,500', $this->row('SELECT log FROM organised_crime_logs WHERE gang_id = ?', [$gangId])['log']);
        $this->assertNotNull($this->row("SELECT id FROM events WHERE user_id = ? AND body LIKE '%succeeded%'", [$this->boss]));
    }

    public function test_staff_gang_editor_and_wars(): void
    {
        $gangId = (int) $this->gang()['id'];
        $this->createUser('it_owner', ['role' => 'admin']);
        $this->loginAs('it_owner');

        $this->assertSame(200, $this->get('/admin/data/gangs')->status());
        $this->assertSame(200, $this->get("/admin/data/gangs/{$gangId}/edit")->status());
        $this->post("/admin/data/gangs/{$gangId}", [
            '_method' => 'PUT', 'name' => 'IT Gang', 'prefix' => 'ITG', 'description' => '', 'announcement' => '',
            'money' => '5', 'crystals' => '0', 'respect' => '50', 'capacity' => '9', 'president_id' => (string) $this->boss, 'vice_president_id' => '',
        ]);
        $gang = $this->gang();
        $this->assertSame('ITG', $gang['prefix']);
        $this->assertNull($gang['vice_president_id']);

        $this->post("/admin/data/gangs/{$gangId}", [
            '_method' => 'PUT', 'name' => 'IT Gang', 'prefix' => '', 'description' => '', 'announcement' => '',
            'money' => '5', 'crystals' => '0', 'respect' => '50', 'capacity' => '9', 'president_id' => '999999999', 'vice_president_id' => '',
        ], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame('ITG', $this->gang()['prefix'], 'unknown player id rejected');

        $this->assertSame(200, $this->get('/admin/gang-wars')->status());
    }
}
