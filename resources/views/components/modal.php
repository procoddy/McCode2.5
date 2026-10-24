<?php
/**
 * Native <dialog>; open with document.getElementById(id).showModal().
 *
 * @var string $id
 * @var string $title
 * @var string $body  Pre-rendered, trusted HTML
 */
?>
<dialog id="<?= e($id) ?>" aria-labelledby="<?= e($id) ?>-title"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-lg border border-gray-200 bg-white p-6 text-gray-900 shadow-xl backdrop:bg-black/50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-100">
    <div class="mb-4 flex items-start justify-between gap-4">
        <h2 id="<?= e($id) ?>-title" class="text-lg font-semibold"><?= e($title) ?></h2>
        <form method="dialog"><button class="cursor-pointer text-2xl leading-none text-gray-400 hover:text-gray-600" aria-label="Close">&times;</button></form>
    </div>
    <div><?= $body ?></div>
</dialog>
