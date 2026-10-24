<?php
/** @var array{until: string, reason: string} $sentence */
$this->set('title', 'Federal jail');
?>
<div class="card mx-auto max-w-lg text-center">
    <p class="text-5xl" aria-hidden="true">&#9878;</p>
    <h1 class="mt-2 text-2xl font-semibold">You are in federal jail</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400">
        Staff have locked your account until <strong><?= e(date('F j, Y \a\t g:i a', strtotime($sentence['until']))) ?></strong>.
    </p>
    <p class="mt-4 rounded-md bg-gray-100 p-3 text-left text-sm whitespace-pre-line dark:bg-gray-800"><?= e($sentence['reason']) ?></p>
    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">If you think this is a mistake, contact the staff team.</p>
</div>
