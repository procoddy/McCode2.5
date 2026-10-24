<?php
/**
 * @var array<string, mixed> $gang
 * @var bool $isLeader
 * @var list<array<string, mixed>> $logs
 */
$this->set('title', 'Organised crimes');
?>
<?= $this->partial('gang.nav', ['gang' => $gang, 'isLeader' => $isLeader, 'active' => '/gang/crimes']) ?>

<div class="card mb-6">
    <?php if ($gang['organised_crime_id']): ?>
        Your gang is planning <strong><?= e($gang['organised_crime_name']) ?></strong>.
        It happens <?= e(date('F j, g:i a', strtotime($gang['crime_ready_at']))) ?>.
    <?php else: ?>
        Your gang is not planning a crime.
    <?php endif; ?>
</div>

<?php if ($logs !== []): ?>
    <h2 class="mb-3 font-semibold">Past crimes</h2>
    <div class="space-y-3">
        <?php foreach ($logs as $log): ?>
            <details class="card">
                <summary class="cursor-pointer">
                    <?= e($log['crime_name']) ?> —
                    <span class="<?= $log['result'] === 'success' ? 'text-green-600' : 'text-red-600' ?>"><?= $log['result'] === 'success' ? 'Success, ' . e(App\Support\Money::of((int) $log['money'])->format()) : 'Failed' ?></span>
                    <span class="text-sm text-gray-500">· <?= e(date('M j, Y', strtotime($log['created_at']))) ?></span>
                </summary>
                <p class="mt-2 text-sm whitespace-pre-line"><?= e($log['log']) ?></p>
            </details>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
