<?php $this->set('title', 'Not Found'); ?>
<div class="py-6 text-center">
    <p class="text-5xl font-bold text-brand-600">404</p>
    <h1 class="mt-2 text-xl font-semibold">Not Found</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400"><?= e($message ?? 'The page you are looking for could not be found.') ?></p>
    <a href="/" class="btn btn-secondary mt-6">Return home</a>
</div>
