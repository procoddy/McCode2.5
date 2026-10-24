<?php
/** @var list<array<string, mixed>> $forums */
$this->set('title', 'Forums');
$labels = ['public' => 'Forums', 'gang' => 'Your gang', 'staff' => 'Staff'];
$grouped = [];
foreach ($forums as $forum) {
    $grouped[$forum['access']][] = $forum;
}
?>
<h1 class="mb-6 text-2xl font-semibold">Forums</h1>

<?php if ($forums === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">No forums have been set up yet.</div>
<?php endif; ?>

<?php foreach ($grouped as $access => $list): ?>
    <section class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <h2 class="border-b border-gray-200 bg-gray-50 px-4 py-2 font-semibold dark:border-gray-800 dark:bg-gray-800/50"><?= e($labels[$access]) ?></h2>
        <ul class="divide-y divide-gray-200 dark:divide-gray-800">
            <?php foreach ($list as $forum): ?>
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                    <div>
                        <a href="/forums/<?= (int) $forum['id'] ?>" class="link font-medium"><?= e($forum['name']) ?></a>
                        <?php if ($forum['description'] !== ''): ?><p class="text-sm text-gray-500 dark:text-gray-400"><?= e($forum['description']) ?></p><?php endif; ?>
                    </div>
                    <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                        <?= number_format((int) $forum['topic_count']) ?> topics · <?= number_format((int) $forum['post_count']) ?> posts
                        <?php if ($forum['last_post_at']): ?><br>Last post <?= e(date('M j, g:i a', strtotime($forum['last_post_at']))) ?><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
