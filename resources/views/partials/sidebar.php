<?php
/**
 * Main game menu. On phones it is one horizontally scrolling row; from md up
 * it is a column with section headings.
 *
 * @var App\Modules\Players\Models\Player|null $player
 */
$crystals = config('game.crystals_name');
$inGang = isset($player) && $player->gangId !== null;
/*
 * A badge loads its own content, polls every 60s, and reloads when a response
 * sends the matching HX-Trigger (e.g. `announcements-read`). hx-target="this"
 * overrides the body's hx-target="#content".
 */
$badge = fn (string $url, string $event) => '<span hx-get="' . $url . '" hx-trigger="load, every 60s, ' . $event . ' from:body" hx-target="this" hx-swap="innerHTML" hx-indicator="this"></span>';
$sections = [
    '' => [
        ['/dashboard', 'Home'],
        ['/events', 'Events', $badge('/events/unread-count', 'events-read')],
        ['/mail', 'Mail', $badge('/mail/unread-count', 'mail-read')],
        ['/announcements', 'Announcements', $badge('/announcements/unread-count', 'announcements-read')],
    ],
    'Play' => [
        ['/inventory', 'Inventory'],
        ['/crimes', 'Crimes'],
        ['/battle-tent', 'Battle Tent'],
        [$inGang ? '/gang' : '/gangs', $inGang ? 'Your Gang' : 'Gangs'],
        ['/slots', 'Slots'],
    ],
    'Money' => [
        ['/shops', 'Shops'],
        ['/item-market', 'Item Market'],
        ['/crystal-market', "{$crystals} Market"],
        ['/bank', 'Bank'],
        ['/cyber-bank', 'Cyber Bank'],
        ['/crystal-temple', "{$crystals} Temple"],
    ],
    'Community' => [
        ['/forums', 'Forums'],
        ['/newspaper', 'Newspaper'],
        ['/polls', 'Polls'],
        ['/players', 'Players'],
        ['/players/online', 'Online'],
        ['/friends', 'Friends'],
        ['/enemies', 'Black List'],
        ['/staff-list', 'Staff'],
        ['/stats', 'Statistics'],
    ],
];
?>
<aside class="border-b border-gray-200 bg-white md:w-52 md:shrink-0 md:border-0 md:bg-transparent dark:border-gray-800 dark:bg-gray-900 md:dark:bg-transparent">
    <?php /* [&>a]: styles every link, including ones `php bin/make.php resource` adds at the marker. */ ?>
    <nav class="flex gap-1 overflow-x-auto px-4 py-2 text-sm md:flex-col md:px-0 md:py-0 [&>a]:shrink-0 [&>a]:rounded-md [&>a]:px-3 [&>a]:py-2 [&>a]:font-medium [&>a]:text-gray-700 [&>a]:hover:bg-gray-200 dark:[&>a]:text-gray-300 dark:[&>a]:hover:bg-gray-800" aria-label="Game">
        <?php foreach ($sections as $heading => $links): ?>
            <?php if ($heading !== ''): ?>
                <p class="hidden px-3 pt-4 pb-1 text-xs font-semibold tracking-wide text-gray-500 uppercase md:block dark:text-gray-400"><?= e($heading) ?></p>
            <?php endif; ?>
            <?php foreach ($links as $link): ?>
                <a href="<?= e($link[0]) ?>" class="flex items-center justify-between gap-2"><?= e($link[1]) ?><?= $link[2] ?? '' ?></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        <?php /* make:resource links */ ?>
    </nav>
</aside>
