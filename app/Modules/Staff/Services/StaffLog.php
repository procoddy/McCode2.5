<?php

declare(strict_types=1);

namespace App\Modules\Staff\Services;

use App\Contracts\ClockInterface;
use App\Core\Database;
use App\Core\Request;

/**
 * Legacy stafflog_add(): records who did what from where.
 */
final class StaffLog
{
    public function __construct(
        private readonly Database $db,
        private readonly ClockInterface $clock,
        private readonly Request $request,
    ) {
    }

    public function add(string $action): void
    {
        $this->db->insert('staff_logs', [
            'user_id' => $this->request->attribute('user')?->id,
            'action' => mb_substr($action, 0, 255),
            'ip' => $this->request->ip(),
            'created_at' => $this->clock->now()->format('Y-m-d H:i:s'),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function latest(int $limit = 20): array
    {
        return $this->db->fetchAll(
            'SELECT s.*, u.username FROM staff_logs s LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.created_at DESC, s.id DESC LIMIT ' . max(1, $limit),
        );
    }
}
