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
    hx-headers="<?= e(json_encode(['X-CSRF-TOKEN' => csrf_token(), 'X-Layout' => 'admin'])) ?>"
>
    <?= $this->partial('progress') ?>
    <header class="border-b-2 border-brand-600 bg-gray-900 text-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 md:px-6">
            <a href="/admin" class="font-bold">Staff Panel</a>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden text-gray-400 sm:inline"><?= e($user->username ?? '') ?></span>
                <a href="/dashboard" hx-boost="false" class="text-gray-300 hover:text-white">&larr; Back to game</a>
            </div>
        </div>
    </header>
    <div class="mx-auto max-w-7xl md:flex md:gap-6 md:px-6 md:py-6">
        <?= $this->partial('staff-menu') ?>
        <main id="content" class="min-w-0 flex-1 px-4 py-6 md:px-0 md:py-0">
            <?= $this->partial('notifications') ?>
            <?= $content ?>
        </main>
    </div>
</body>
</html>
