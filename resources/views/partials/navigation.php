<header class="border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 md:px-6">
        <a href="/dashboard" class="text-lg font-bold tracking-tight"><?= e($appName) ?></a>
        <nav class="flex items-center gap-4 text-sm">
            <?= $this->partial('player-status', ['oob' => false]) ?>
            <?php if (isset($user)): ?>
                <?php if (!empty($isStaff)): ?>
                    <a href="/admin" class="link" hx-boost="false">Staff</a>
                <?php endif; ?>
            <?php endif; ?>
            <form method="post" action="/logout">
                <?= csrf_field() ?>
                <button type="submit" class="cursor-pointer text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">Log out</button>
            </form>
        </nav>
    </div>
</header>
