<?php
/**
 * @var string $box inbox|outbox
 * @var App\Support\Pagination $messages
 */
$this->set('title', $box === 'inbox' ? 'Mailbox' : 'Sent mail');
$tab = fn (string $href, string $label, bool $active) => '<a href="' . $href . '" class="rounded-md px-3 py-1.5 text-sm font-medium '
    . ($active ? 'bg-gray-200 dark:bg-gray-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800') . '">' . $label . '</a>';
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Mailbox</h1>
    <a href="/mail/compose" class="btn btn-primary">Compose</a>
</div>

<nav class="mb-4 flex flex-wrap gap-1" aria-label="Mailbox folders">
    <?= $tab('/mail', 'Inbox', $box === 'inbox') ?>
    <?= $tab('/mail/outbox', 'Sent', $box === 'outbox') ?>
    <a href="/mail/archive/<?= $box ?>" hx-boost="false" class="rounded-md px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800">Download archive</a>
</nav>

<?php if ($messages->items === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400"><?= $box === 'inbox' ? 'You have no messages.' : "You haven't sent any messages." ?></div>
<?php else: ?>
    <ul class="divide-y divide-gray-200 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xs dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
        <?php foreach ($messages->items as $m): ?>
            <?php $unread = $box === 'inbox' && $m['read_at'] === null; ?>
            <li>
                <a href="/mail/<?= (int) $m['id'] ?>" class="flex flex-wrap items-baseline justify-between gap-2 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <span class="<?= $unread ? 'font-semibold' : '' ?>">
                        <?php if ($unread): ?><span class="mr-1 inline-block size-2 rounded-full bg-brand-600" aria-label="Unread"></span><?php endif; ?>
                        <?= e($m['subject']) ?>
                        <span class="text-sm font-normal text-gray-500">— <?= $box === 'inbox' ? 'from' : 'to' ?> <?= e($m['other_username'] ?? 'SYSTEM') ?></span>
                    </span>
                    <time class="text-sm text-gray-500" datetime="<?= e($m['created_at']) ?>"><?= e(date('M j, g:i a', strtotime($m['created_at']))) ?></time>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?= $this->component('pagination', ['paginator' => $messages, 'url' => $box === 'inbox' ? '/mail' : '/mail/outbox']) ?>

    <?php if ($box === 'inbox'): ?>
        <form method="post" action="/mail/delete-all" class="mt-6" hx-confirm="Delete every message in your inbox?">
            <?= csrf_field() ?>
            <?= $this->component('button', ['label' => 'Delete all messages', 'type' => 'submit', 'variant' => 'danger']) ?>
        </form>
    <?php endif; ?>
<?php endif; ?>
