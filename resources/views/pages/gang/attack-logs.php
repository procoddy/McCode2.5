<?php
/**
 * @var array<string, mixed> $gang
 * @var bool $isLeader
 * @var list<array<string, mixed>> $logs
 */
$this->set('title', 'Gang attack logs');
$outcomes = ['mugged' => 'mugged', 'hospitalized' => 'hospitalized', 'left' => 'beat', 'lost' => 'lost to'];
?>
<?= $this->partial('gang.nav', ['gang' => $gang, 'isLeader' => $isLeader, 'active' => '/gang/attack-logs']) ?>

<?php if ($logs === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">No fights involving your members yet.</div>
<?php else: ?>
    <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white text-sm dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
        <?php foreach ($logs as $log): ?>
            <li class="flex flex-wrap justify-between gap-2 px-4 py-2">
                <span>
                    <a href="/players/<?= (int) $log['attacker_id'] ?>" class="link"><?= e($log['attacker']) ?></a>
                    <?= e($outcomes[$log['outcome']] ?? $log['outcome']) ?>
                    <a href="/players/<?= (int) $log['defender_id'] ?>" class="link"><?= e($log['defender']) ?></a>
                    <?php if ((int) $log['money_stolen'] > 0): ?><span class="text-gray-500">(<?= e(App\Support\Money::of((int) $log['money_stolen'])->format()) ?>)</span><?php endif; ?>
                </span>
                <time class="text-gray-500"><?= e(date('M j, g:i a', strtotime($log['created_at']))) ?></time>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
