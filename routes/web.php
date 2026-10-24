<?php

declare(strict_types=1);

use App\Core\Request;
use App\Core\Response;
use App\Modules\Announcements\Controllers\AnnouncementController;
use App\Core\Router;
use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Banking\Controllers\BankController;
use App\Modules\Banking\Controllers\TransferController;
use App\Modules\Events\Controllers\EventController;
use App\Modules\Players\Controllers\PlayerDirectoryController;
use App\Modules\Players\Controllers\ProfileController;
use App\Modules\Casino\Controllers\SlotsController;
use App\Modules\Combat\Controllers\AttackController;
use App\Modules\Community\Controllers\CommunityController;
use App\Modules\Crimes\Controllers\CrimeController;
use App\Modules\Players\Controllers\UserListController;
use App\Modules\Forums\Controllers\ForumController;
use App\Modules\Gangs\Controllers\GangController;
use App\Modules\Gangs\Controllers\YourGangController;
use App\Modules\Inventory\Controllers\InventoryController;
use App\Modules\Inventory\Controllers\ShopController;
use App\Modules\Mail\Controllers\MailController;
use App\Modules\Market\Controllers\MarketController;
use App\Modules\CrystalTemple\Controllers\CrystalTempleController;
use App\Modules\Users\Controllers\DashboardController;

return static function (Router $router): void {
    $router->get('/', fn () => Response::redirect('/dashboard'));

    $router->group(['middleware' => ['guest']], static function (Router $router): void {
        $router->get('/login', [AuthController::class, 'showLogin']);
        $router->post('/login', [AuthController::class, 'login'], ['throttle:5,1']);
        $router->get('/register', [AuthController::class, 'showRegister']);
        $router->post('/register', [AuthController::class, 'register'], ['throttle:5,10']);
        $router->post('/register/check', [AuthController::class, 'checkAvailability'], ['throttle:30,1']);
    });

    $router->group(['middleware' => ['auth', 'player']], static function (Router $router): void {
        $router->get('/dashboard', [DashboardController::class, 'index']);
        $router->get('/dashboard/stats', [DashboardController::class, 'stats']);
        $router->post('/logout', [AuthController::class, 'logout']);
        $router->get('/announcements', [AnnouncementController::class, 'index']);
        $router->get('/announcements/unread-count', [AnnouncementController::class, 'unreadBadge']);

        // Banks (legacy bank.php, cyberbank.php): one controller, account type from config/game.php
        foreach (['bank' => '/bank', 'cyber' => '/cyber-bank'] as $type => $url) {
            $router->get($url, fn (BankController $c, Request $r) => $c->show($r, $type));
            $router->post("{$url}/open", fn (BankController $c, Request $r) => $c->open($r, $type));
            $router->post("{$url}/deposit", fn (BankController $c, Request $r) => $c->deposit($r, $type));
            $router->post("{$url}/withdraw", fn (BankController $c, Request $r) => $c->withdraw($r, $type));
        }

        $router->get('/crystal-temple', [CrystalTempleController::class, 'index']);
        $router->post('/crystal-temple/refill', [CrystalTempleController::class, 'refill']);
        $router->post('/crystal-temple/iq', [CrystalTempleController::class, 'iq']);
        $router->post('/crystal-temple/money', [CrystalTempleController::class, 'money']);

        $router->get('/events', [EventController::class, 'index']);
        $router->get('/events/unread-count', [EventController::class, 'unreadBadge']);
        $router->post('/events/clear', [EventController::class, 'clear']);

        // Legacy userlist.php, usersonline.php, stafflist.php, stats.php, viewuser.php
        $router->get('/players', [PlayerDirectoryController::class, 'index']);
        $router->get('/players/online', [PlayerDirectoryController::class, 'online']);
        $router->get('/staff-list', [PlayerDirectoryController::class, 'staff']);
        $router->get('/stats', [PlayerDirectoryController::class, 'stats']);
        $router->get('/players/{id}', [ProfileController::class, 'show']);
        $router->get('/players/{id}/report', [ProfileController::class, 'reportForm']);
        $router->post('/players/{id}/report', [ProfileController::class, 'report'], ['throttle:5,10']);

        // Legacy sendbank.php / sendcyber.php
        $router->get('/players/{id}/send/{type}', [TransferController::class, 'form']);
        $router->post('/players/{id}/send/{type}', [TransferController::class, 'send'], ['throttle:20,1']);

        $router->get('/crimes', [CrimeController::class, 'index']);
        $router->post('/crimes/{id}', [CrimeController::class, 'commit']);

        // Legacy inventory.php, iteminfo.php, itemuse.php, equip*.php, unequip.php, itemsell.php, itemsend.php
        $router->get('/inventory', [InventoryController::class, 'index']);
        $router->post('/inventory/unequip/{slot}', [InventoryController::class, 'unequip']);
        $router->get('/inventory/{id}', [InventoryController::class, 'show']);
        $router->post('/inventory/{id}/use', [InventoryController::class, 'use']);
        $router->post('/inventory/{id}/equip', [InventoryController::class, 'equip']);
        $router->post('/inventory/{id}/sell', [InventoryController::class, 'sell']);
        $router->post('/inventory/{id}/send', [InventoryController::class, 'send'], ['throttle:20,1']);

        // Legacy shops.php, itembuy.php
        $router->get('/shops', [ShopController::class, 'index']);
        $router->get('/shops/{id}', [ShopController::class, 'show']);
        $router->post('/shops/{id}/buy/{item}', [ShopController::class, 'buy']);

        // Legacy itemmarket.php, imadd.php, cmarket.php
        $router->get('/item-market', [MarketController::class, 'items']);
        $router->post('/item-market', [MarketController::class, 'listItem']);
        $router->post('/item-market/{id}/buy', [MarketController::class, 'buyItem']);
        $router->post('/item-market/{id}/remove', [MarketController::class, 'withdrawItem']);
        $router->get('/crystal-market', [MarketController::class, 'crystals']);
        $router->post('/crystal-market', [MarketController::class, 'listCrystals']);
        $router->post('/crystal-market/{id}/buy', [MarketController::class, 'buyCrystals']);
        $router->post('/crystal-market/{id}/remove', [MarketController::class, 'withdrawCrystals']);

        // Legacy attack.php, attackwon.php, attackbeat.php, attacktake.php, attacklost.php, battletent.php
        $router->get('/attack/{id}', [AttackController::class, 'show']);
        $router->post('/attack/{id}', [AttackController::class, 'strike'], ['throttle:120,1']);
        $router->post('/attack/{id}/finish', [AttackController::class, 'finish']);
        $router->post('/attack/{id}/flee', [AttackController::class, 'flee']);
        $router->get('/battle-tent', [AttackController::class, 'battleTent']);

        // Legacy blacklist.php and friendslist.php
        foreach (['friends', 'enemies'] as $list) {
            $router->get("/{$list}", fn (UserListController $c, Request $r) => $c->index($r, $list));
            $router->post("/{$list}/add", fn (UserListController $c, Request $r) => $c->add($r, $list));
            $router->post("/{$list}/{id}/remove", fn (UserListController $c, Request $r, int $id) => $c->remove($r, $list, $id));
            $router->post("/{$list}/{id}/comment", fn (UserListController $c, Request $r, int $id) => $c->comment($r, $list, $id));
        }

        // Legacy mailbox.php, dlarchive.php
        $router->get('/mail', [MailController::class, 'inbox']);
        $router->get('/mail/outbox', [MailController::class, 'outbox']);
        $router->get('/mail/compose', [MailController::class, 'compose']);
        $router->get('/mail/unread-count', [MailController::class, 'unreadBadge']);
        $router->get('/mail/archive/{box}', [MailController::class, 'archive']);
        $router->post('/mail', [MailController::class, 'send'], ['throttle:20,1']);
        $router->post('/mail/delete-all', [MailController::class, 'destroyAll']);
        $router->get('/mail/{id}', [MailController::class, 'show']);
        $router->post('/mail/{id}/delete', [MailController::class, 'destroy']);

        // Gang list and creategang.php
        $router->get('/gangs', [GangController::class, 'index']);
        $router->get('/gangs/create', [GangController::class, 'createForm']);
        $router->post('/gangs/create', [GangController::class, 'create']);
        $router->get('/gangs/{id}', [GangController::class, 'show']);
        $router->post('/gangs/{id}/apply', [GangController::class, 'apply'], ['throttle:10,1']);

        // Legacy yourgang.php
        $router->get('/gang', [YourGangController::class, 'home']);
        $router->get('/gang/members', [YourGangController::class, 'members']);
        $router->post('/gang/members/{id}/kick', [YourGangController::class, 'kick']);
        $router->get('/gang/wars', [YourGangController::class, 'wars']);
        $router->get('/gang/attack-logs', [YourGangController::class, 'attackLogs']);
        $router->get('/gang/crimes', [YourGangController::class, 'crimes']);
        $router->get('/gang/forum', [YourGangController::class, 'forum']);
        $router->post('/gang/donate', [YourGangController::class, 'donate']);
        $router->post('/gang/leave', [YourGangController::class, 'leave']);
        $router->get('/gang/staff', [YourGangController::class, 'staff']);
        $router->post('/gang/staff/applications/{id}/{decision}', [YourGangController::class, 'decideApplication']);
        $router->post('/gang/staff/surrenders/{id}/accept', [YourGangController::class, 'acceptSurrender']);
        $router->post('/gang/staff/{action}', [YourGangController::class, 'staffAction']);

        // Legacy slotsmachine.php
        $router->get('/slots', [SlotsController::class, 'index']);
        $router->post('/slots', [SlotsController::class, 'spin'], ['throttle:60,1']);

        // Forums (public, gang and staff)
        $router->get('/forums', [ForumController::class, 'index']);
        $router->get('/forums/topics/{id}', [ForumController::class, 'topic']);
        $router->post('/forums/topics/{id}/reply', [ForumController::class, 'reply'], ['throttle:20,1']);
        $router->post('/forums/topics/{id}/{action}', [ForumController::class, 'moderate']);
        $router->get('/forums/posts/{id}/edit', [ForumController::class, 'editPost']);
        $router->post('/forums/posts/{id}', [ForumController::class, 'updatePost']);
        $router->post('/forums/posts/{id}/delete', [ForumController::class, 'deletePost']);
        $router->get('/forums/{id}', [ForumController::class, 'forum']);
        $router->post('/forums/{id}/topics', [ForumController::class, 'createTopic'], ['throttle:10,1']);

        // Polls and newspaper
        $router->get('/polls', [CommunityController::class, 'polls']);
        $router->post('/polls/{id}/vote', [CommunityController::class, 'vote']);
        $router->get('/newspaper', [CommunityController::class, 'newspaper']);

        // `php bin/make.php resource` adds routes above the marker below; keep the marker.
        // make:resource routes
    });
};
