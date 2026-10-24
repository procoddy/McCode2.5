<?php

declare(strict_types=1);

namespace App\Modules\Gangs\Repositories;

use App\Core\Database;

/**
 * Respect changes from fights between gangs at war (legacy attackwon.php etc.).
 */
class GangWarRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function atWar(int $gangA, int $gangB): bool
    {
        return $this->db->fetchColumn(
            'SELECT 1 FROM gang_wars
             WHERE (declarer_gang_id = ? AND declared_gang_id = ?) OR (declarer_gang_id = ? AND declared_gang_id = ?)',
            [$gangA, $gangB, $gangB, $gangA],
        ) !== null;
    }

    /**
     * Move respect from the losing gang to the winning one. A gang left with no
     * respect is destroyed: members are released (FK SET NULL) and its wars end.
     * Returns true if the losing gang was destroyed.
     */
    public function transferRespect(int $fromGang, int $toGang, int $amount): bool
    {
        return $this->db->transaction(function () use ($fromGang, $toGang, $amount): bool {
            $this->db->query('UPDATE gangs SET respect = respect - ? WHERE id = ?', [$amount, $fromGang]);
            $this->db->query('UPDATE gangs SET respect = respect + ? WHERE id = ?', [$amount, $toGang]);

            return $this->db->query('DELETE FROM gangs WHERE id = ? AND respect <= 0', [$fromGang])->rowCount() === 1;
        });
    }
}
