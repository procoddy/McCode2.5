<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;

final class AnnouncementSeeder
{
    public function run(Database $db): void
    {
        if ((int) $db->fetchColumn('SELECT COUNT(*) FROM announcements') > 0) {
            return;
        }

        $adminId = $db->fetchColumn('SELECT id FROM users WHERE username = ?', ['admin']);

        $db->insert('announcements', [
            'author_id' => $adminId,
            'body' => "Welcome to McCode 2.5!\nThe game has moved to a new engine. Report anything odd to staff.",
            'created_at' => date('Y-m-d H:i:s', time() - 86400),
        ]);
        $db->insert('announcements', [
            'author_id' => $adminId,
            'body' => 'Double XP weekend starts Friday at 6pm.',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
