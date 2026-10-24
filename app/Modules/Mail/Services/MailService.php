<?php

declare(strict_types=1);

namespace App\Modules\Mail\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Exceptions\ValidationException;
use App\Modules\Mail\Repositories\MailRepository;

/**
 * Legacy mailbox.php compose / send, gang mass mail and staff mass mail.
 */
final class MailService
{
    public const SUBJECT_MAX = 255;
    public const BODY_MAX = 5000;

    public function __construct(
        private readonly MailRepository $mail,
        private readonly Database $db,
        private readonly ClockInterface $clock,
    ) {
    }

    /** Why this player can't send mail right now, or null. */
    public function banReason(int $userId): ?string
    {
        $row = $this->db->fetch('SELECT mail_banned_until, mail_ban_reason FROM users WHERE id = ?', [$userId]);

        if ($row === null || $row['mail_banned_until'] === null || $row['mail_banned_until'] <= $this->now()) {
            return null;
        }

        return 'You are banned from mail until ' . date('F j, Y, g:i a', strtotime($row['mail_banned_until']))
            . ($row['mail_ban_reason'] ? ". Reason: {$row['mail_ban_reason']}" : '.');
    }

    public function send(int $senderId, int $recipientId, string $subject, string $body): int
    {
        if (($reason = $this->banReason($senderId)) !== null) {
            throw ValidationException::withMessages(['body' => $reason]);
        }
        if ($this->db->fetchColumn('SELECT 1 FROM users WHERE id = ?', [$recipientId]) === null) {
            throw ValidationException::withMessages(['to' => 'That player does not exist.']);
        }

        return $this->mail->send($senderId, $recipientId, $this->subject($subject), $body, $this->now());
    }

    /** Legacy yourgang.php mass mailer. */
    public function sendToGang(int $senderId, int $gangId, string $subject, string $body): int
    {
        return $this->mail->sendToMany($senderId, 'u.gang_id = ?', [$gangId], $this->subject($subject), $body, $this->now());
    }

    /** Legacy staff_special.php mass mail: everyone, or holders of one staff role. */
    public function sendToAll(?int $senderId, string $subject, string $body, ?int $roleId = null): int
    {
        return $roleId === null
            ? $this->mail->sendToMany($senderId, "u.account_type <> 'NPC'", [], $this->subject($subject), $body, $this->now())
            : $this->mail->sendToMany($senderId, 'u.id IN (SELECT user_id FROM user_staff_roles WHERE staff_role_id = ?)', [$roleId], $this->subject($subject), $body, $this->now());
    }

    private function subject(string $subject): string
    {
        $subject = trim($subject);

        return $subject === '' ? '(no subject)' : mb_substr($subject, 0, self::SUBJECT_MAX);
    }

    private function now(): string
    {
        return $this->clock->now()->format('Y-m-d H:i:s');
    }
}
