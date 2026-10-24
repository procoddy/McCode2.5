<!doctype html>
<html lang="en">
<head>
    <?= $this->partial('head') ?>
</head>
<body
    class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100"
    hx-boost="true"
    hx-target="#content"
    hx-indicator="#progress"
    hx-headers="<?= e(json_encode(['X-CSRF-TOKEN' => csrf_token(), 'X-Layout' => 'guest'])) ?>"
>
    <?= $this->partial('progress') ?>
    <div class="w-full max-w-sm">
        <p class="mb-6 text-center text-2xl font-bold tracking-tight"><?= e($appName) ?></p>
        <main id="content" class="card">
            <?= $this->partial('notifications') ?>
            <?= $content ?>
        </main>
    </div>
</body>
</html>
