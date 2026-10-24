<?php
/**
 * @var array<string, mixed> $gang
 * @var bool $isLeader
 * @var list<array<string, mixed>> $members
 */
$this->set('title', 'Gang members');
?>
<?= $this->partial('gang.nav', ['gang' => $gang, 'isLeader' => $isLeader, 'active' => '/gang/members']) ?>
<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
            <tr>
                <th class="px-4 py-2 font-semibold">Member</th>
                <th class="px-4 py-2 font-semibold">Level</th>
                <th class="px-4 py-2 font-semibold">Days in gang</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
            <?php foreach ($members as $m): ?>
                <tr>
                    <td class="px-4 py-2">
                        <a href="/players/<?= (int) $m['id'] ?>" class="link"><?= e($m['username']) ?></a>
                        <?php if ((int) $m['id'] === (int) $gang['president_id']): ?><span class="text-xs text-gray-500">President</span><?php elseif ((int) $m['id'] === (int) $gang['vice_president_id']): ?><span class="text-xs text-gray-500">Vice-president</span><?php endif; ?>
                    </td>
                    <td class="px-4 py-2"><?= (int) $m['level'] ?></td>
                    <td class="px-4 py-2"><?= $m['gang_joined_at'] ? (int) floor((time() - strtotime($m['gang_joined_at'])) / 86400) : 0 ?></td>
                    <td class="px-4 py-2 text-right">
                        <?php if ($isLeader && (int) $m['id'] !== (int) $gang['president_id']): ?>
                            <form method="post" action="/gang/members/<?= (int) $m['id'] ?>/kick" hx-confirm="Kick <?= e($m['username']) ?> out of the gang?">
                                <?= csrf_field() ?>
                                <button type="submit" class="link cursor-pointer text-red-600">Kick</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
