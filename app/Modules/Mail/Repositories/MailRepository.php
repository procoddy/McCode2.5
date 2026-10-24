<?php

declare(strict_types=1);

namespace App\Modules\Mail\Repositories;

use App\Core\Database;
use App\Support\Pagination;

/**
 * One row per message; the sender's outbox reads the same row, so a message
 * the recipient deletes also leaves the outbox (as in the legacy game).
 */
class MailRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function inbox(int $userId, int $page, int $perPage = 25): Pagination
    {
        $pagination = new Pagination((int) $this->db->fetchColumn('SELECT COUNT(*) FROM mail WHERE recipient_id = ?', [$userId]), $perPage, $page);

        return $pagination->withItems($this->db->fetchAll(
            "SELECT m.*, u.username AS other_username
             FROM mail m LEFT JOIN users u ON u.id = m.sender_id
             WHERE m.recipient_id = ?
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
            [$userId],
        ));
    }

    public function outbox(int $userId, int $page, int $perPage = 25): Pagination
    {
        $pagination = new Pagination((int) $this->db->fetchColumn('SELECT COUNT(*) FROM mail WHERE sender_id = ?', [$userId]), $perPage, $page);

        return $pagination->withItems($this->db->fetchAll(
            "SELECT m.*, u.username AS other_username
             FROM mail m LEFT JOIN users u ON u.id = m.recipient_id
             WHERE m.sender_id = ?
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
            [$userId],
        ));
    }

    /** A message the user sent or received. */
    public function findFor(int $id, int $userId): ?array
    {
        return $this->db->fetch(
            'SELECT m.*, s.username AS sender_username, r.username AS recipient_username
             FROM mail m
             LEFT JOIN users s ON s.id = m.sender_id
             LEFT JOIN users r ON r.id = m.recipient_id
             WHERE m.id = ? AND (m.recipient_id = ? OR m.sender_id = ?)',
            [$id, $userId, $userId],
        );
    }

    public function send(?int $senderId, int $recipientId, string $subject, string $body, string $now): int
    {
        return $this->db->insert('mail', [
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'subject' => $subject,
            'body' => $body,
            'created_at' => $now,
        ]);
    }

    /**
     * One copy per recipient chosen by $where (an SQL condition on users u
     * built by the caller from fixed strings, with $params). Returns how many were sent.
     */
    public function sendToMany(?int $senderId, string $where, array $params, string $subject, string $body, string $now): int
    {
        return $this->db->query(
            "INSERT INTO mail (sender_id, recipient_id, subject, body, created_at)
             SELECT ?, u.id, ?, ?, ? FROM users u WHERE {$where}",
            [$senderId, $subject, $body, $now, ...$params],
        )->rowCount();
    }

    public function markRead(int $id, int $recipientId, string $now): void
    {
        $this->db->query('UPDATE mail SET read_at = ? WHERE id = ? AND recipient_id = ? AND read_at IS NULL', [$now, $id, $recipientId]);
    }

    public function delete(int $id, int $recipientId): bool
    {
        return $this->db->delete('mail', ['id' => $id, 'recipient_id' => $recipientId]) === 1;
    }

    public function deleteAll(int $recipientId): int
    {
        return $this->db->delete('mail', ['recipient_id' => $recipientId]);
    }

    public function unreadCount(int $userId): int
    {
        return (int) $this->db->fetchColumn('SELECT COUNT(*) FROM mail WHERE recipient_id = ? AND read_at IS NULL', [$userId]);
    }

    /** Everything in one box, oldest first, for the archive download. */
    public function archive(int $userId, string $box): array
    {
        [$mine, $other] = $box === 'outbox' ? ['sender_id', 'recipient_id'] : ['recipient_id', 'sender_id'];

        return $this->db->fetchAll(
            "SELECT m.subject, m.body, m.created_at, u.id AS other_id, u.username AS other_username
             FROM mail m LEFT JOIN users u ON u.id = m.{$other}
             WHERE m.{$mine} = ?
             ORDER BY m.created_at DESC, m.id DESC",
            [$userId],
        );
    }
}
