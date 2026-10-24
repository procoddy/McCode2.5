<?php

declare(strict_types=1);

namespace Tests\Integration;

final class CommunityTest extends IntegrationTestCase
{
    private int $alice;
    private int $bob;
    private int $forum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alice = $this->createUser('it_alice');
        $this->bob = $this->createUser('it_bob');
        $this->forum = $this->db->insert('forums', ['name' => 'IT General', 'access' => 'public']);
    }

    public function test_topics_replies_edits_and_moderation(): void
    {
        $this->loginAs('it_alice');
        $this->assertSame(200, $this->get('/forums')->status());
        $this->post("/forums/{$this->forum}/topics", ['title' => 'Hello world', 'body' => 'First <script>']);
        $topic = (int) $this->row("SELECT id FROM forum_topics WHERE title = 'Hello world'")['id'];
        $this->assertStringContainsString('First &lt;script&gt;', $this->get("/forums/topics/{$topic}")->content());
        $this->assertSame(200, $this->get("/forums/{$this->forum}")->status());

        $this->loginAs('it_bob');
        $this->post("/forums/topics/{$topic}/reply", ['body' => 'Hi Alice']);
        $reply = (int) $this->row('SELECT id FROM forum_posts WHERE topic_id = ? AND user_id = ?', [$topic, $this->bob])['id'];
        $first = (int) $this->row('SELECT id FROM forum_posts WHERE topic_id = ? AND user_id = ?', [$topic, $this->alice])['id'];
        $this->assertSame(403, $this->get("/forums/posts/{$first}/edit")->status(), "can't edit someone else's post");
        $this->post("/forums/posts/{$reply}", ['body' => 'Hi Alice!']);
        $this->assertSame('Hi Alice!', $this->row('SELECT body FROM forum_posts WHERE id = ?', [$reply])['body']);
        $this->assertSame(403, $this->post("/forums/topics/{$topic}/lock")->status(), 'not a moderator');

        $this->createUser('it_owner', ['role' => 'admin']);
        $this->loginAs('it_owner');
        $this->post("/forums/topics/{$topic}/lock");
        $this->assertSame(1, (int) $this->row('SELECT locked FROM forum_topics WHERE id = ?', [$topic])['locked']);

        $this->loginAs('it_bob');
        $this->post("/forums/topics/{$topic}/reply", ['body' => 'Locked?'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(2, (int) $this->row('SELECT COUNT(*) c FROM forum_posts WHERE topic_id = ?', [$topic])['c']);

        $this->loginAs('it_owner');
        $this->post("/forums/posts/{$reply}/delete");
        $this->post("/forums/posts/{$first}/delete");
        $this->assertNull($this->row('SELECT id FROM forum_topics WHERE id = ?', [$topic]), 'last post deleted takes the topic');
    }

    public function test_forum_access_and_bans(): void
    {
        $staffForum = $this->db->insert('forums', ['name' => 'IT Staff room', 'access' => 'staff']);
        $gang = $this->db->insert('gangs', ['name' => 'IT G', 'created_at' => date('Y-m-d H:i:s')]);
        $gangForum = $this->db->insert('forums', ['name' => 'IT G forum', 'access' => 'gang', 'gang_id' => $gang]);
        $this->db->update('users', ['gang_id' => $gang, 'forum_banned_until' => date('Y-m-d H:i:s', time() + 3600)], ['id' => $this->alice]);

        $this->loginAs('it_alice');
        $this->assertSame(403, $this->get("/forums/{$staffForum}")->status());
        $this->assertSame(200, $this->get("/forums/{$gangForum}")->status());
        $this->assertStringNotContainsString('IT Staff room', $this->get('/forums')->content());
        $this->post("/forums/{$gangForum}/topics", ['title' => 'Banned', 'body' => 'x'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertNull($this->row("SELECT id FROM forum_topics WHERE title = 'Banned'"), 'forum banned');

        $this->loginAs('it_bob');
        $this->assertSame(403, $this->get("/forums/{$gangForum}")->status(), 'other gangs');
    }

    public function test_polls_and_newspaper(): void
    {
        $this->createUser('it_owner', ['role' => 'admin']);
        $this->loginAs('it_owner');
        $this->assertSame(200, $this->get('/admin/polls')->status());
        $this->post('/admin/polls', ['question' => 'IT Best colour?', 'choices' => ['Red', 'Blue', '', '']]);
        $poll = (int) $this->row("SELECT id FROM polls WHERE question = 'IT Best colour?'")['id'];
        $this->post('/admin/newspaper', ['body' => 'IT Breaking news']);

        $this->loginAs('it_alice');
        $this->post("/polls/{$poll}/vote", ['choice' => '1']);
        $this->post("/polls/{$poll}/vote", ['choice' => '0'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertSame(1, (int) $this->row('SELECT COUNT(*) c FROM poll_votes WHERE poll_id = ?', [$poll])['c'], 'one vote each');
        $this->assertSame(200, $this->get('/polls')->status());
        $this->assertStringContainsString('IT Breaking news', $this->get('/newspaper')->content());

        $this->loginAs('it_owner');
        $this->post("/admin/polls/{$poll}/close");
        $closed = $this->row('SELECT active, winning_choice FROM polls WHERE id = ?', [$poll]);
        $this->assertSame(0, (int) $closed['active']);
        $this->assertSame(1, (int) $closed['winning_choice']);
    }

    public function test_staff_tools(): void
    {
        $this->db->update('users', ['last_login_ip' => '203.0.113.9'], ['id' => $this->alice]);
        $this->db->update('users', ['signup_ip' => '203.0.113.9'], ['id' => $this->bob]);
        $this->createUser('it_owner', ['role' => 'admin']);
        $this->loginAs('it_owner');

        foreach (['/admin/bans', '/admin/ip-search', '/admin/ip-search?ip=203.0.113.9', '/admin/newspaper', '/admin/mass-mail', '/admin/donator-packs', '/admin/data/forums', '/admin/data/gangs'] as $page) {
            $this->assertSame(200, $this->get($page)->status(), $page);
        }
        $this->assertStringContainsString('it_bob', $this->get('/admin/ip-search?ip=203.0.113.9')->content());

        $this->post('/admin/bans/mail', ['username' => 'it_alice', 'days' => '2', 'reason' => 'Spam']);
        $this->assertNotNull($this->row('SELECT mail_banned_until FROM users WHERE id = ?', [$this->alice])['mail_banned_until']);
        $this->post("/admin/bans/mail/{$this->alice}/remove");
        $this->assertNull($this->row('SELECT mail_banned_until FROM users WHERE id = ?', [$this->alice])['mail_banned_until']);

        $this->post('/admin/mass-jail', ['ids' => [(string) $this->alice, (string) $this->bob], 'days' => '3', 'reason' => 'Multi']);
        $this->assertSame(2, (int) $this->row('SELECT COUNT(*) c FROM federal_jail WHERE user_id IN (?, ?)', [$this->alice, $this->bob])['c']);

        $this->post('/admin/donator-packs', ['username' => 'it_alice', 'pack' => 'standard']);
        $alice = $this->row('SELECT money, crystals, donator_until FROM users WHERE id = ?', [$this->alice]);
        $this->assertSame(50, (int) $alice['crystals']);
        $this->assertNotNull($alice['donator_until']);

        $before = (int) $this->row('SELECT COUNT(*) c FROM mail')['c'];
        $this->post('/admin/mass-mail', ['role_id' => '0', 'subject' => 'IT Hello', 'body' => 'Everyone']);
        $this->assertGreaterThan($before, (int) $this->row('SELECT COUNT(*) c FROM mail')['c']);

        $this->post('/admin/data/forums', ['name' => 'IT New forum', 'description' => '', 'access' => 'public', 'sort_order' => '1']);
        $this->assertNotNull($this->row("SELECT id FROM forums WHERE name = 'IT New forum'"));
        $this->post('/admin/data/forums', ['name' => 'IT Bad', 'description' => '', 'access' => 'everyone', 'sort_order' => '1'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertNull($this->row("SELECT id FROM forums WHERE name = 'IT Bad'"), 'unknown choice rejected');
    }
}
