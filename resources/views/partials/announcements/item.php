<?php
/**
 * @var App\Modules\Announcements\Models\Announcement $announcement
 * @var bool $isNew
 */
?>
<li class="card">
    <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
        <time datetime="<?= e(date(DATE_ATOM, strtotime($announcement->createdAt))) ?>">
            <?= e(date('F j, Y, g:i a', strtotime($announcement->createdAt))) ?>
        </time>
        <?php if ($announcement->authorName !== null): ?>
            <span>· <?= e($announcement->authorName) ?></span>
        <?php endif; ?>
        <?php if ($isNew): ?>
            <span class="rounded-full bg-brand-600 px-2 py-0.5 text-xs font-semibold text-white">New</span>
        <?php endif; ?>
    </div>
    <?php /* Escaped first, then line breaks added: staff text is never treated as HTML. */ ?>
    <p class="leading-relaxed"><?= nl2br(e($announcement->body)) ?></p>
</li>
