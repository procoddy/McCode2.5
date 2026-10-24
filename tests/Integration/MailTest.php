<?php

declare(strict_types=1);

namespace Tests\Integration;

final class MailTest extends IntegrationTestCase
{
    public function test_send_read_reply_delete_and_archive(): void
    {
        $alice = $this->createUser('it_alice');
        $bob = $this->createUser('it_bob');
        $this->loginAs('it_alice');

        $this->post('/mail', ['to' => (string) $bob, 'subject' => 'Hello', 'body' => 'Hi <b>Bob</b>']);
        $mail = $this->row('SELECT id, read_at FROM mail WHERE sender_id = ? AND recipient_id = ?', [$alice, $bob]);
        $this->assertNotNull($mail);
        $this->assertSame(200, $this->get('/mail/outbox')->status());
        $this->assertSame(200, $this->get("/mail/{$mail['id']}")->status(), 'sender can read it');
        $this->assertNull($this->row('SELECT read_at FROM mail WHERE id = ?', [$mail['id']])['read_at'], 'sender reading does not mark it read');

        $this->loginAs('it_bob');
        $this->assertStringContainsString('1', $this->get('/mail/unread-count')->content());
        $page = $this->get("/mail/{$mail['id']}");
        $this->assertStringContainsString('Hi &lt;b&gt;Bob&lt;/b&gt;', $page->content(), 'escaped');
        $this->assertNotNull($this->row('SELECT read_at FROM mail WHERE id = ?', [$mail['id']])['read_at']);
        $this->assertSame(200, $this->get("/mail/compose?reply={$mail['id']}")->status());

        $archive = $this->get('/mail/archive/inbox');
        $this->assertStringContainsString('attachment', (string) $archive->getHeader('Content-Disposition'));
        $this->assertStringContainsString('Hello', $archive->content());

        $this->post("/mail/{$mail['id']}/delete");
        $this->assertNull($this->row('SELECT id FROM mail WHERE id = ?', [$mail['id']]));
    }

    public function test_mail_ban_and_privacy(): void
    {
        $alice = $this->createUser('it_alice', ['mail_banned_until' => date('Y-m-d H:i:s', time() + 3600), 'mail_ban_reason' => 'Spam']);
        $bob = $this->createUser('it_bob');
        $carol = $this->createUser('it_carol');
        $this->loginAs('it_alice');

        $this->post('/mail', ['to' => (string) $bob, 'body' => 'x'], ['HTTP_REFERER' => 'http://localhost/x']);
        $this->assertNull($this->row('SELECT id FROM mail WHERE sender_id = ?', [$alice]), 'banned');

        $this->db->insert('mail', ['sender_id' => $bob, 'recipient_id' => $carol, 'subject' => 's', 'body' => 'b', 'created_at' => date('Y-m-d H:i:s')]);
        $id = (int) $this->db->pdo()->lastInsertId();
        $this->assertSame(404, $this->get("/mail/{$id}")->status(), "can't read other people's mail");
    }
}
