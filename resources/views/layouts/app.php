<!doctype html>
<html lang="en">
<head>
    <?= $this->partial('head') ?>
</head>
<body
    class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100"
    hx-boost="true"
    hx-target="#content"
    hx-indicator="#progress"
    hx-headers="<?= e(json_encode(['X-CSRF-TOKEN' => csrf_token(), 'X-Layout' => 'app'])) ?>"
>
    <?= $this->partial('progress') ?>
    <?= $this->partial('navigation') ?>
    <div class="mx-auto max-w-6xl md:flex md:gap-6 md:px-6 md:py-6">
        <?= $this->partial('sidebar') ?>
        <main id="content" class="min-w-0 flex-1 px-4 py-6 md:px-0 md:py-0">
            <?= $this->partial('notifications') ?>
            <?= $content ?>
        </main>
    </div>
</body>
</html>
