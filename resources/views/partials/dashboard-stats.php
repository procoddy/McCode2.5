<?php
/**
 * Swapped in place by the dashboard's Refresh button (hx-get + hx-target).
 *
 * @var App\Modules\Auth\Models\User $player
 * @var string $updatedAt
 */
?>
<section id="stats" class="grid gap-4 sm:grid-cols-3">
    <div class="card">
        <p class="text-sm text-gray-500 dark:text-gray-400">Level</p>
        <p class="mt-1 text-2xl font-semibold"><?= e($player->level) ?></p>
    </div>
    <div class="card">
        <p class="text-sm text-gray-500 dark:text-gray-400">Money</p>
        <p class="mt-1 text-2xl font-semibold"><?= e($player->money->format()) ?></p>
    </div>
    <div class="card">
        <p class="text-sm text-gray-500 dark:text-gray-400">Last login</p>
        <p class="mt-1 text-lg font-semibold"><?= e($player->lastLoginAt ?? 'First visit') ?></p>
    </div>
    <p class="text-xs text-gray-500 sm:col-span-3 dark:text-gray-400">Updated <?= e($updatedAt) ?></p>
</section>
