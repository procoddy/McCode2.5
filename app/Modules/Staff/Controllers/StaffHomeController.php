<?php

declare(strict_types=1);

namespace App\Modules\Staff\Controllers;

use App\Console\CronJobs;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Modules\Settings\Contracts\SettingsRepositoryInterface;
use App\Modules\Staff\Services\StaffLog;
use App\Modules\Staff\Services\StaffPermissions;

/**
 * Legacy staff.php: index (system info, staff log, notepad), basic settings
 * and manually firing cron.
 */
final class StaffHomeController
{
    public function __construct(
        private readonly Database $db,
        private readonly SettingsRepositoryInterface $settings,
        private readonly StaffLog $staffLog,
        private readonly StaffPermissions $permissions,
        private readonly Session $session,
    ) {
    }

    public function index(Request $request): Response
    {
        $isAdmin = $this->permissions->can($request->attribute('user'), 'administrator');

        return view('pages.staff.index', [
            'system' => $isAdmin ? [
                'PHP version' => PHP_VERSION,
                'Database version' => (string) $this->db->fetchColumn('SELECT VERSION()'),
                'Players' => number_format((int) $this->db->fetchColumn('SELECT COUNT(*) FROM users')),
                'Environment' => (string) config('app.env'),
            ] : null,
            'recentActions' => $isAdmin ? $this->staffLog->latest(20) : [],
            'notepad' => (string) $this->settings->get('staff_pad', ''),
        ], 'admin');
    }

    public function saveNotepad(Request $request): Response
    {
        $this->settings->set('staff_pad', mb_substr((string) $request->input('pad', ''), 0, 65535));
        $this->staffLog->add('Updated the staff notepad');
        $this->session->flash('status', 'Staff notepad saved.');

        return Response::redirect('/admin');
    }

    public function settings(): Response
    {
        return view('pages.staff.settings', ['settings' => $this->settings->all()], 'admin');
    }

    public function saveSettings(Request $request): Response
    {
        foreach ($this->settings->all() as $name => $setting) {
            if ($name === 'staff_pad') {
                continue;
            }

            $value = $request->input($name);
            $value = match ($setting['type']) {
                'bool' => empty($value) ? '0' : '1',
                'int' => (string) max(0, (int) $value),
                default => mb_substr(trim((string) $value), 0, 1000),
            };
            $this->settings->set($name, $value);
        }

        $this->staffLog->add('Updated the basic game settings');
        $this->session->flash('status', 'Settings saved.');

        return Response::redirect('/admin/settings');
    }

    public function cron(): Response
    {
        return view('pages.staff.cron', [], 'admin');
    }

    public function runCron(Request $request, CronJobs $cron): Response
    {
        $job = (string) $request->input('job');

        if (!isset(CronJobs::JOBS[$job])) {
            $this->session->flash('status', 'Choose a cron job to run.');
        } else {
            $this->session->flash('status', implode(' ', $cron->run($job)));
            $this->staffLog->add("Manually ran cron: {$job}");
        }

        return Response::redirect('/admin/cron');
    }
}
