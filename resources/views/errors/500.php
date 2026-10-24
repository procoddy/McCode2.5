<?php
$status ??= 500;
$this->set('title', $status >= 500 ? 'Error' : 'Request failed');
?>
<div class="py-6 text-center">
    <p class="text-5xl font-bold text-brand-600"><?= e($status) ?></p>
    <h1 class="mt-2 text-xl font-semibold"><?= $status >= 500 ? 'Something went wrong' : 'Request failed' ?></h1>
    <p class="mt-2 text-gray-600 dark:text-gray-400"><?= e($message ?? 'Server Error') ?></p>
    <a href="/" class="btn btn-secondary mt-6">Return home</a>
</div>
<?php if (!empty($exception)): ?>
    <pre class="mt-6 overflow-x-auto rounded-md bg-gray-100 p-4 text-left text-xs dark:bg-gray-950"><?= e($exception::class . ': ' . $exception->getMessage() . "\n" . $exception->getFile() . ':' . $exception->getLine() . "\n\n" . $exception->getTraceAsString()) ?></pre>
<?php endif; ?>
