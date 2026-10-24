<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\NotFoundException;
use App\Modules\Staff\Services\StaffLog;

/**
 * Legacy staff_gangs.php?action=gwar / gwardelete.
 */
final class GangWarController
{
    public function __construct(private readonly Database $db, private readonly StaffLog $staffLog, private readonly Session $session)
    {
    }

    public function index(): Response
    {
        return view('pages.staff.gang-wars', [
            'wars' => $this->db->fetchAll(
                'SELECT w.id, w.created_at, a.id AS declarer_id, a.name AS declarer, b.id AS declared_id, b.name AS declared
                 FROM gang_wars w JOIN gangs a ON a.id = w.declarer_gang_id JOIN gangs b ON b.id = w.declared_gang_id
                 ORDER BY w.created_at DESC',
            ),
        ], 'admin');
    }

    public function destroy(int $id): Response
    {
        $war = $this->db->fetch(
            'SELECT a.name AS declarer, b.name AS declared FROM gang_wars w
             JOIN gangs a ON a.id = w.declarer_gang_id JOIN gangs b ON b.id = w.declared_gang_id WHERE w.id = ?',
            [$id],
        ) ?? throw new NotFoundException('That war does not exist.');

        $this->db->delete('gang_wars', ['id' => $id]);
        $this->staffLog->add("Ended the war between {$war['declarer']} and {$war['declared']}");
        $this->session->flash('status', 'War ended.');

        return Response::redirect('/admin/gang-wars');
    }
}
