<?php
/**
 * @var App\Support\Pagination $paginator
 * @var string                 $url Base URL; ?page=N is appended
 */
if (!$paginator->hasPages()) {
    return;
}
$link = fn (int $page) => e($url . (str_contains($url, '?') ? '&' : '?') . 'page=' . $page);
$item = 'rounded-md px-3 py-1.5 text-sm';
?>
<nav class="mt-4 flex flex-wrap items-center gap-1" aria-label="Pagination">
    <?php if ($paginator->hasPrevious()): ?>
        <a href="<?= $link($paginator->currentPage - 1) ?>" rel="prev" class="<?= $item ?> hover:bg-gray-200 dark:hover:bg-gray-800">&lsaquo; Prev</a>
    <?php endif; ?>
    <?php foreach ($paginator->window() as $page): ?>
        <?php if ($page === $paginator->currentPage): ?>
            <span aria-current="page" class="<?= $item ?> bg-brand-600 font-semibold text-white"><?= $page ?></span>
        <?php else: ?>
            <a href="<?= $link($page) ?>" class="<?= $item ?> hover:bg-gray-200 dark:hover:bg-gray-800"><?= $page ?></a>
        <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($paginator->hasNext()): ?>
        <a href="<?= $link($paginator->currentPage + 1) ?>" rel="next" class="<?= $item ?> hover:bg-gray-200 dark:hover:bg-gray-800">Next &rsaquo;</a>
    <?php endif; ?>
</nav>
