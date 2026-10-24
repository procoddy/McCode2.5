<?php $this->set('title', 'Forbidden'); ?>
<div class="py-6 text-center">
    <p class="text-5xl font-bold text-brand-600">403</p>
    <h1 class="mt-2 text-xl font-semibold">Forbidden</h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400"><?= e($message ?? 'You are not allowed to do that.') ?></p>
    <a href="/" class="btn btn-secondary mt-6">Return home</a>
</div>
