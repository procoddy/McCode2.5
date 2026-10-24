<?php
/** @var list<array{id: int, name: string, description: ?string}> $shops */
$this->set('title', 'Shops');
?>
<h1 class="mb-2 text-2xl font-semibold">Shops</h1>
<p class="mb-6 text-gray-600 dark:text-gray-400">You begin looking through town and you see a few shops.</p>

<?php if ($shops === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">There are no shops in this city.</div>
<?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2">
        <?php foreach ($shops as $shop): ?>
            <a href="/shops/<?= (int) $shop['id'] ?>" class="card block transition hover:border-brand-500">
                <h2 class="font-semibold"><?= e($shop['name']) ?></h2>
                <?php if ($shop['description']): ?>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400"><?= e($shop['description']) ?></p>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
