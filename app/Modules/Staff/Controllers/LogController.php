<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Modules\Staff\Services\StaffLog;
use App\Modules\Staff\Support\StaffLogViews;
use App\Support\Pagination;

/**
 * Legacy staff_logs.php. Viewing a log is itself logged, as before.
 */
final class LogController
{
    private const PER_PAGE = 100;

    public function __construct(private readonly Database $db, private readonly StaffLog $staffLog)
    {
    }

    public function show(Request $request, string $key): Response
    {
        $log = StaffLogViews::all()[$key] ?? throw new NotFoundException();

        $pagination = new Pagination(
            (int) $this->db->fetchColumn("SELECT COUNT(*) FROM {$log['from']}"),
            self::PER_PAGE,
            (int) $request->query('page', 1),
        );
        $rows = $this->db->fetchAll(
            "SELECT {$log['select']} FROM {$log['from']} ORDER BY {$log['order']}
             LIMIT {$pagination->perPage} OFFSET {$pagination->offset()}",
        );

        if ($key !== 'staff') {
            $this->staffLog->add("Viewed the {$log['title']} log (page {$pagination->currentPage})");
        }

        return view('pages.staff.logs.show', [
            'key' => $key,
            'log' => $log,
            'rows' => $pagination->withItems($rows),
        ], 'admin');
    }
}
