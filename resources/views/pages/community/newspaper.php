<?php
/** @var array<string, mixed>|null $paper */
$this->set('title', 'Newspaper');
?>
<h1 class="mb-6 text-2xl font-semibold">Newspaper</h1>
<article class="card">
    <?php if ($paper === null): ?>
        <p class="text-gray-500">There's no news yet.</p>
    <?php else: ?>
        <div class="whitespace-pre-line"><?= e($paper['body']) ?></div>
        <p class="mt-4 text-xs text-gray-500">Last updated <?= e(date('F j, Y', strtotime($paper['updated_at']))) ?><?= $paper['editor'] ? ' by ' . e($paper['editor']) : '' ?></p>
    <?php endif; ?>
</article>
