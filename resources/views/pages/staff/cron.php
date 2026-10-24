<?php $this->set('title', 'Run cron'); ?>
<h1 class="mb-2 text-2xl font-semibold">Run cron</h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
    These normally run on a schedule (see the README). Running one here does it immediately for every player.
</p>

<div class="grid gap-4 sm:grid-cols-2">
    <?php foreach (App\Console\CronJobs::JOBS as $job => [$label, $description]): ?>
        <form method="post" action="/admin/cron" class="card space-y-3" hx-confirm="Run the <?= e(strtolower($label)) ?> now?">
            <?= csrf_field() ?>
            <input type="hidden" name="job" value="<?= e($job) ?>">
            <h2 class="font-semibold"><?= e($label) ?></h2>
            <p class="text-sm text-gray-600 dark:text-gray-400"><?= e($description) ?></p>
            <?= $this->component('button', ['label' => 'Run now', 'type' => 'submit', 'variant' => 'secondary']) ?>
        </form>
    <?php endforeach; ?>
</div>
