<?php $this->set('title', 'Dashboard'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Welcome back, <?= e($user->username) ?></h1>
    <?php /* HTMX partial update: fetches /dashboard/stats and replaces #stats only. */ ?>
    <button type="button" class="btn btn-secondary"
            hx-get="/dashboard/stats" hx-target="#stats" hx-swap="outerHTML">
        Refresh
    </button>
</div>

<?= $this->partial('dashboard-stats', ['player' => $user, 'updatedAt' => $updatedAt]) ?>
