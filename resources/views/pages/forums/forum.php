<?php
/**
 * @var array<string, mixed> $forum
 * @var App\Support\Pagination $topics
 * @var string|null $banReason
 */
$this->set('title', $forum['name']);
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($forum['name']) ?></h1>
    <a href="/forums" class="link">&larr; All forums</a>
</div>

<?php if ($topics->items === []): ?>
    <div class="card mb-6 text-center text-gray-500 dark:text-gray-400">No topics yet. Start the first one below.</div>
<?php else: ?>
    <ul class="mb-4 divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xs dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
        <?php foreach ($topics->items as $t): ?>
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <?php if ($t['pinned']): ?><span class="mr-1 rounded bg-brand-600 px-1.5 py-0.5 text-xs text-white">Pinned</span><?php endif; ?>
                    <?php if ($t['locked']): ?><span class="mr-1 rounded bg-gray-500 px-1.5 py-0.5 text-xs text-white">Locked</span><?php endif; ?>
                    <a href="/forums/topics/<?= (int) $t['id'] ?>" class="link font-medium"><?= e($t['title']) ?></a>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= $t['description'] !== '' ? e($t['description']) . ' · ' : '' ?>by <?= e($t['author'] ?? 'deleted player') ?>
                    </p>
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    <?= number_format((int) $t['post_count']) ?> posts<br>
                    Last by <?= e($t['last_poster'] ?? '—') ?>, <?= e(date('M j, g:i a', strtotime($t['last_post_at']))) ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= $this->component('pagination', ['paginator' => $topics, 'url' => "/forums/{$forum['id']}"]) ?>
<?php endif; ?>

<?php if ($banReason !== null): ?>
    <?= $this->component('alert', ['message' => $banReason, 'type' => 'error']) ?>
<?php else: ?>
    <form method="post" action="/forums/<?= (int) $forum['id'] ?>/topics" class="card mt-6 max-w-2xl space-y-4">
        <?= csrf_field() ?>
        <h2 class="font-semibold">New topic</h2>
        <?= $this->component('form-field', ['name' => 'title', 'label' => 'Title', 'value' => old('title'), 'attrs' => ['maxlength' => '150']]) ?>
        <?= $this->component('form-field', ['name' => 'description', 'label' => 'Description (optional)', 'value' => old('description'), 'attrs' => ['maxlength' => '255']]) ?>
        <?= $this->component('form-field', ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'value' => old('body')]) ?>
        <?= $this->component('button', ['label' => 'Post topic', 'type' => 'submit']) ?>
    </form>
<?php endif; ?>
