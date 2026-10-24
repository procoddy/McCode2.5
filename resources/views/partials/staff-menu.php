<?php
/**
 * Legacy smenu.php: only links the viewer is allowed to use are shown.
 *
 * @var callable(string ...): bool $staffCan
 */
$staffCan ??= fn () => false;
?>
<aside class="border-b border-gray-200 bg-white md:w-56 md:shrink-0 md:border-0 md:bg-transparent dark:border-gray-800 dark:bg-gray-900 md:dark:bg-transparent">
    <details open>
        <summary class="cursor-pointer px-4 py-2 text-sm font-semibold md:hidden">Staff menu</summary>
        <nav class="space-y-4 px-4 pb-4 text-sm md:px-0">
            <?php foreach (App\Modules\Staff\Support\StaffMenu::sections() as $section): ?>
                <?php $links = array_filter($section['links'], fn ($link) => $staffCan(...$link['permissions'])); ?>
                <?php if ($links !== []): ?>
                    <div>
                        <p class="mb-1 px-3 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"><?= e($section['title']) ?></p>
                        <div class="flex flex-col *:rounded-md *:px-3 *:py-1.5 *:text-gray-700 *:hover:bg-gray-200 dark:*:text-gray-300 dark:*:hover:bg-gray-800">
                            <?php foreach ($links as $link): ?>
                                <a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </details>
</aside>
