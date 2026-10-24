<?php
/**
 * @var list<array<string, mixed>> $gangs
 * @var App\Modules\Players\Models\Player $player
 */
$this->set('title', 'Gangs');
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Gangs</h1>
    <?php if ($player->gangId === null): ?>
        <a href="/gangs/create" class="btn btn-primary">Create a gang</a>
    <?php else: ?>
        <a href="/gang" class="btn btn-secondary">Your gang</a>
    <?php endif; ?>
</div>

<?php if ($gangs === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">No gangs exist yet.</div>
<?php else: ?>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-2 font-semibold">Gang</th>
                    <th class="px-4 py-2 font-semibold">Members</th>
                    <th class="px-4 py-2 font-semibold">President</th>
                    <th class="px-4 py-2 font-semibold">Respect</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($gangs as $g): ?>
                    <tr>
                        <td class="px-4 py-2"><a href="/gangs/<?= (int) $g['id'] ?>" class="link"><?= e($g['name']) ?></a> <?php if ($g['prefix'] !== ''): ?><span class="text-gray-500">[<?= e($g['prefix']) ?>]</span><?php endif; ?></td>
                        <td class="px-4 py-2"><?= (int) $g['member_count'] ?>/<?= (int) $g['capacity'] ?></td>
                        <td class="px-4 py-2"><?php if ($g['president_id']): ?><a href="/players/<?= (int) $g['president_id'] ?>" class="link"><?= e($g['president_username']) ?></a><?php else: ?>—<?php endif; ?></td>
                        <td class="px-4 py-2"><?= number_format((int) $g['respect']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
