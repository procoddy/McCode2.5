<?php
/**
 * @var App\Support\Pagination $players
 * @var string $sort
 * @var string $direction
 * @var string $onlineSince
 */
$this->set('title', 'Players');
$base = "/players?sort={$sort}&dir={$direction}";
$sortLink = function (string $column, string $label) use ($sort, $direction): string {
    $dir = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $arrow = $sort === $column ? ($direction === 'asc' ? ' ↑' : ' ↓') : '';

    return '<a class="hover:underline" href="/players?sort=' . $column . '&dir=' . $dir . '">' . e($label) . $arrow . '</a>';
};
?>
<h1 class="mb-6 text-2xl font-semibold">Players</h1>

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
            <tr class="*:px-4 *:py-3 *:font-medium">
                <th><?= $sortLink('id', 'ID') ?></th>
                <th><?= $sortLink('username', 'Name') ?></th>
                <th><?= $sortLink('level', 'Level') ?></th>
                <th><?= $sortLink('money', 'Money') ?></th>
                <th>Gender</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            <?php foreach ($players->items as $p): ?>
                <tr class="*:px-4 *:py-2.5">
                    <td class="text-gray-500"><?= (int) $p['id'] ?></td>
                    <td>
                        <a class="link" href="/players/<?= (int) $p['id'] ?>">
                            <?= $p['gang_prefix'] ? e($p['gang_prefix']) . ' ' : '' ?><?= e($p['username']) ?>
                        </a>
                        <?php if ($p['donator_until'] && $p['donator_until'] > date('Y-m-d H:i:s')): ?>
                            <span title="Donator" class="text-brand-600">&#9733;</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $p['level'] ?></td>
                    <td><?= e(App\Support\Money::of((int) $p['money'])->format()) ?></td>
                    <td><?= e($p['gender']) ?></td>
                    <td><?= $this->partial('players.online-dot', ['online' => $p['last_seen_at'] > $onlineSince]) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= $this->component('pagination', ['paginator' => $players, 'url' => $base]) ?>
