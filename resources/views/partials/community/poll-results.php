<?php /** @var array<string, mixed> $poll */ ?>
<ul class="space-y-2 text-sm">
    <?php foreach ($poll['choices'] as $i => $choice): ?>
        <?php $percent = $poll['total_votes'] > 0 ? round($poll['votes'][$i] / $poll['total_votes'] * 100) : 0; ?>
        <li>
            <div class="flex justify-between gap-2">
                <span><?= e($choice) ?><?= $poll['winning_choice'] !== null && (int) $poll['winning_choice'] === $i ? ' <strong>(winner)</strong>' : '' ?></span>
                <span class="text-gray-500"><?= number_format($poll['votes'][$i]) ?> (<?= $percent ?>%)</span>
            </div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800"><div class="h-full rounded-full bg-brand-600" style="width: <?= $percent ?>%"></div></div>
        </li>
    <?php endforeach; ?>
</ul>
<p class="text-xs text-gray-500"><?= number_format($poll['total_votes']) ?> votes</p>
