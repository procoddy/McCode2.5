<?php
/**
 * @var App\Support\Pagination $announcements
 * @var string|null $lastReadAt
 */
$this->set('title', 'Announcements');
?>
<h1 class="mb-6 text-2xl font-semibold">Announcements</h1>

<?php if ($announcements->items === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">No announcements yet.</div>
<?php else: ?>
    <ol class="space-y-4">
        <?php foreach ($announcements->items as $announcement): ?>
            <?= $this->partial('announcements.item', [
                'announcement' => $announcement,
                'isNew' => $announcement->isNewerThan($lastReadAt),
            ]) ?>
        <?php endforeach; ?>
    </ol>

    <?= $this->component('pagination', ['paginator' => $announcements, 'url' => '/announcements']) ?>
<?php endif; ?>
