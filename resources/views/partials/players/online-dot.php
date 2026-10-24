<?php /** @var bool $online */ ?>
<span class="inline-flex items-center gap-1.5 <?= $online ? 'text-green-700 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' ?>">
    <span class="size-2 rounded-full <?= $online ? 'bg-green-500' : 'bg-gray-400' ?>" aria-hidden="true"></span>
    <?= $online ? 'Online' : 'Offline' ?>
</span>
