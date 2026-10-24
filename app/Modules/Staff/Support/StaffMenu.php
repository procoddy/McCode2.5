<?php

declare(strict_types=1);

namespace App\Modules\Staff\Support;

/**
 * Staff panel navigation (legacy smenu.php). A link is shown when the user has
 * any of its permissions; an empty list means "any staff member".
 */
final class StaffMenu
{
    /**
     * @return list<array{title: string, links: list<array{label: string, url: string, permissions: list<string>}>}>
     */
    public static function sections(): array
    {
        $link = fn (string $label, string $url, string ...$permissions) => compact('label', 'url', 'permissions');

        $resources = [];
        foreach (config('staff_resources', []) as $key => $resource) {
            $resources[] = $link($resource['title'], "/admin/data/{$key}", $resource['permission']);
        }

        return [
            ['title' => 'General', 'links' => [
                $link('Staff home', '/admin'),
                $link('Basic settings', '/admin/settings', 'administrator'),
                $link('Announcements', '/admin/announcements', 'administrator'),
                $link('Run cron', '/admin/cron', 'administrator'),
            ]],
            ['title' => 'Staff roles', 'links' => [
                $link('Roles', '/admin/roles', 'manage_roles'),
                $link('Grant / revoke', '/admin/roles/members', 'manage_staff', 'manage_roles'),
            ]],
            ['title' => 'Players', 'links' => [
                $link('Find & edit players', '/admin/users', 'manage_users'),
                $link('Credit player', '/admin/users/credit', 'credit_user'),
                $link('Give item', '/admin/users/give-item', 'credit_item'),
                $link('Mass payment', '/admin/users/mass-credit', 'credit_all_users'),
                $link('Player reports', '/admin/reports', 'manage_player_reports'),
                $link('Federal jail', '/admin/fedjail', 'manage_punishments'),
                $link('Mail & forum bans', '/admin/bans', 'manage_punishments'),
                $link('IP search', '/admin/ip-search', 'manage_punishments'),
                $link('Give donator pack', '/admin/donator-packs', 'manage_donator_packs'),
            ]],
            ['title' => 'Community', 'links' => [
                $link('Polls', '/admin/polls', 'manage_polls'),
                $link('Newspaper', '/admin/newspaper', 'edit_newspaper'),
                $link('Mass mail', '/admin/mass-mail', 'mass_mail'),
            ]],
            ['title' => 'Game data', 'links' => [...$resources, $link('Gang wars', '/admin/gang-wars', 'manage_gangs'), $link('Challenge bots', '/admin/challenge-bots', 'manage_challenge_bots')]],
            ['title' => 'Logs', 'links' => array_map(
                fn (string $key, array $log) => $link($log['title'], "/admin/logs/{$key}", 'view_logs'),
                array_keys(StaffLogViews::all()),
                StaffLogViews::all(),
            )],
        ];
    }
}
