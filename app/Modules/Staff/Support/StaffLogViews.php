<?php

declare(strict_types=1);

namespace App\Modules\Staff\Support;

/**
 * Log pages at /admin/logs/{key} (legacy staff_logs.php). Each entry is a
 * read-only query; columns map a heading to a result field, with an optional
 * format ('money', 'datetime', 'multi').
 */
final class StaffLogViews
{
    /**
     * @return array<string, array{title: string, from: string, select: string, order: string, columns: array<string, array{0: string, 1?: string}>}>
     */
    public static function all(): array
    {
        return [
            'staff' => [
                'title' => 'Staff actions',
                'from' => 'staff_logs s LEFT JOIN users u ON u.id = s.user_id',
                'select' => 's.created_at, u.username, s.action, s.ip',
                'order' => 's.created_at DESC, s.id DESC',
                'columns' => ['Time' => ['created_at', 'datetime'], 'Staff' => ['username'], 'Action' => ['action'], 'IP' => ['ip']],
            ],
            'attacks' => [
                'title' => 'Attacks',
                'from' => 'attack_logs a LEFT JOIN users u1 ON u1.id = a.attacker_id LEFT JOIN users u2 ON u2.id = a.defender_id',
                'select' => 'a.created_at, u1.username AS attacker, u2.username AS defender, a.result, a.outcome, a.money_stolen',
                'order' => 'a.created_at DESC, a.id DESC',
                'columns' => [
                    'Time' => ['created_at', 'datetime'], 'Attacker' => ['attacker'], 'Defender' => ['defender'],
                    'Result' => ['result'], 'Outcome' => ['outcome'], 'Stolen' => ['money_stolen', 'money'],
                ],
            ],
            'transfers' => [
                'title' => 'Transfers',
                'from' => 'transfer_logs t LEFT JOIN users u1 ON u1.id = t.from_user_id LEFT JOIN users u2 ON u2.id = t.to_user_id',
                'select' => 't.created_at, t.type, u1.username AS sender, u2.username AS receiver, t.amount, t.from_ip, t.to_ip,
                             (t.from_ip IS NOT NULL AND t.from_ip = t.to_ip) AS same_ip',
                'order' => 't.created_at DESC, t.id DESC',
                'columns' => [
                    'Time' => ['created_at', 'datetime'], 'Type' => ['type'], 'From' => ['sender'], 'To' => ['receiver'],
                    'Amount' => ['amount'], 'From IP' => ['from_ip'], 'To IP' => ['to_ip'], 'Same IP?' => ['same_ip', 'multi'],
                ],
            ],
            'items' => [
                'title' => 'Item activity',
                'from' => 'item_logs l LEFT JOIN users u1 ON u1.id = l.user_id LEFT JOIN users u2 ON u2.id = l.other_user_id LEFT JOIN items i ON i.id = l.item_id',
                'select' => 'l.created_at, l.type, u1.username AS player, u2.username AS other_player, i.name AS item, l.quantity, l.price, l.currency',
                'order' => 'l.created_at DESC, l.id DESC',
                'columns' => [
                    'Time' => ['created_at', 'datetime'], 'Type' => ['type'], 'Player' => ['player'], 'Other player' => ['other_player'],
                    'Item' => ['item'], 'Qty' => ['quantity'], 'Price' => ['price'], 'Currency' => ['currency'],
                ],
            ],
            'mail' => [
                'title' => 'Mail',
                'from' => 'mail m LEFT JOIN users u1 ON u1.id = m.sender_id LEFT JOIN users u2 ON u2.id = m.recipient_id',
                'select' => 'm.created_at, u1.username AS sender, u2.username AS recipient, m.subject, LEFT(m.body, 200) AS body',
                'order' => 'm.created_at DESC, m.id DESC',
                'columns' => [
                    'Time' => ['created_at', 'datetime'], 'From' => ['sender'], 'To' => ['recipient'],
                    'Subject' => ['subject'], 'Message' => ['body'],
                ],
            ],
        ];
    }
}
