<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Router;
use App\Modules\Announcements\Controllers\AdminAnnouncementController;
use App\Modules\Staff\Controllers\ChallengeBotController;
use App\Modules\Staff\Controllers\CommunityAdminController;
use App\Modules\Staff\Controllers\FederalJailController;
use App\Modules\Staff\Controllers\GangWarController;
use App\Modules\Staff\Controllers\LogController;
use App\Modules\Staff\Controllers\PunishmentController;
use App\Modules\Staff\Controllers\ResourceController;
use App\Modules\Staff\Controllers\RoleController;
use App\Modules\Staff\Controllers\StaffHomeController;
use App\Modules\Staff\Controllers\UserController;

/*
 * Staff panel. 'staff' lets in anyone with a staff role (or the owner);
 * 'staff:permission' additionally requires that permission (legacy check_access()).
 */
return static function (Router $router): void {
    $router->group(['prefix' => '/admin', 'middleware' => ['auth', 'staff']], static function (Router $router): void {
        $router->get('/', [StaffHomeController::class, 'index']);
        $router->post('/notepad', [StaffHomeController::class, 'saveNotepad']);
        $router->get('/settings', [StaffHomeController::class, 'settings'], ['staff:administrator']);
        $router->post('/settings', [StaffHomeController::class, 'saveSettings'], ['staff:administrator']);
        $router->get('/cron', [StaffHomeController::class, 'cron'], ['staff:administrator']);
        $router->post('/cron', [StaffHomeController::class, 'runCron'], ['staff:administrator']);

        $router->get('/announcements', [AdminAnnouncementController::class, 'index'], ['staff:administrator']);
        $router->post('/announcements', [AdminAnnouncementController::class, 'store'], ['staff:administrator']);

        // Game data editors; each checks its own permission from config/staff_resources.php.
        $router->get('/data/{key}', [ResourceController::class, 'index']);
        $router->get('/data/{key}/create', [ResourceController::class, 'create']);
        $router->post('/data/{key}', [ResourceController::class, 'store']);
        $router->get('/data/{key}/{id}/edit', [ResourceController::class, 'edit']);
        $router->put('/data/{key}/{id}', [ResourceController::class, 'update']);
        $router->delete('/data/{key}/{id}', [ResourceController::class, 'destroy']);

        $router->group(['middleware' => ['staff:manage_roles']], static function (Router $router): void {
            $router->get('/roles', [RoleController::class, 'index']);
            $router->get('/roles/create', [RoleController::class, 'create']);
            $router->post('/roles', [RoleController::class, 'store']);
            $router->get('/roles/{id}/edit', [RoleController::class, 'edit']);
            $router->put('/roles/{id}', [RoleController::class, 'update']);
            $router->delete('/roles/{id}', [RoleController::class, 'destroy']);
        });
        $router->get('/roles/members', [RoleController::class, 'members'], ['staff:manage_staff,manage_roles']);
        $router->post('/roles/grant', [RoleController::class, 'grant'], ['staff:manage_staff,manage_roles']);
        $router->post('/roles/revoke', [RoleController::class, 'revoke'], ['staff:manage_staff,manage_roles']);

        $router->group(['middleware' => ['staff:manage_users']], static function (Router $router): void {
            $router->get('/users', [UserController::class, 'index']);
            $router->get('/users/{id}/edit', [UserController::class, 'edit']);
            $router->put('/users/{id}', [UserController::class, 'update']);
            $router->delete('/users/{id}', [UserController::class, 'destroy']);
            $router->post('/users/{id}/force-logout', [UserController::class, 'forceLogout']);
            $router->post('/users/{id}/notes', [UserController::class, 'saveNotes']);
        });
        $router->get('/users/credit', [UserController::class, 'creditForm'], ['staff:credit_user']);
        $router->post('/users/credit', [UserController::class, 'credit'], ['staff:credit_user']);
        $router->get('/users/{id}/inventory', [UserController::class, 'inventory'], ['staff:view_user_inventory']);
        $router->post('/users/{id}/inventory/{item}/remove', [UserController::class, 'removeInventoryItem'], ['staff:manage_items,credit_item']);
        $router->get('/users/give-item', [UserController::class, 'giveItemForm'], ['staff:credit_item']);
        $router->post('/users/give-item', [UserController::class, 'giveItem'], ['staff:credit_item']);
        $router->get('/users/mass-credit', [UserController::class, 'massCreditForm'], ['staff:credit_all_users']);
        $router->post('/users/mass-credit', [UserController::class, 'massCredit'], ['staff:credit_all_users']);
        $router->get('/reports', [UserController::class, 'reports'], ['staff:manage_player_reports']);
        $router->delete('/reports/{id}', [UserController::class, 'clearReport'], ['staff:manage_player_reports']);

        $router->get('/fedjail', [FederalJailController::class, 'index'], ['staff:manage_punishments']);
        $router->post('/fedjail', [FederalJailController::class, 'jail'], ['staff:manage_punishments']);
        $router->post('/fedjail/{id}/release', [FederalJailController::class, 'release'], ['staff:manage_punishments']);

        $router->group(['middleware' => ['staff:manage_challenge_bots']], static function (Router $router): void {
            $router->get('/challenge-bots', [ChallengeBotController::class, 'index']);
            $router->post('/challenge-bots', [ChallengeBotController::class, 'store']);
            $router->put('/challenge-bots/{id}', [ChallengeBotController::class, 'update']);
            $router->delete('/challenge-bots/{id}', [ChallengeBotController::class, 'destroy']);
        });

        $router->group(['middleware' => ['staff:manage_punishments']], static function (Router $router): void {
            $router->get('/bans', [PunishmentController::class, 'bans']);
            foreach (['mail', 'forum'] as $type) {
                $router->post("/bans/{$type}", fn (PunishmentController $c, Request $r) => $c->ban($r, $type));
                $router->post("/bans/{$type}/{id}/remove", fn (PunishmentController $c, int $id) => $c->unban($id, $type));
            }
            $router->get('/ip-search', [PunishmentController::class, 'ipSearch']);
            $router->post('/mass-jail', [PunishmentController::class, 'massJail']);
        });

        $router->get('/polls', [CommunityAdminController::class, 'polls'], ['staff:manage_polls']);
        $router->post('/polls', [CommunityAdminController::class, 'startPoll'], ['staff:manage_polls']);
        $router->post('/polls/{id}/close', [CommunityAdminController::class, 'endPoll'], ['staff:manage_polls']);
        $router->get('/newspaper', [CommunityAdminController::class, 'newspaper'], ['staff:edit_newspaper']);
        $router->post('/newspaper', [CommunityAdminController::class, 'saveNewspaper'], ['staff:edit_newspaper']);
        $router->get('/mass-mail', [CommunityAdminController::class, 'massMail'], ['staff:mass_mail']);
        $router->post('/mass-mail', [CommunityAdminController::class, 'sendMassMail'], ['staff:mass_mail']);
        $router->get('/donator-packs', [CommunityAdminController::class, 'donatorPacks'], ['staff:manage_donator_packs']);
        $router->post('/donator-packs', [CommunityAdminController::class, 'giveDonatorPack'], ['staff:manage_donator_packs']);

        $router->get('/gang-wars', [GangWarController::class, 'index'], ['staff:manage_gangs']);
        $router->delete('/gang-wars/{id}', [GangWarController::class, 'destroy'], ['staff:manage_gangs']);

        $router->get('/logs/{key}', [LogController::class, 'show'], ['staff:view_logs']);
    });
};
