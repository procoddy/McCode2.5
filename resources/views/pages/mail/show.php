<?php
/**
 * @var array<string, mixed> $message
 * @var int $userId
 */
$this->set('title', $message['subject']);
$received = (int) $message['recipient_id'] === $userId;
$person = fn (?int $id, ?string $name) => $id ? '<a class="link" href="/players/' . $id . '">' . e($name) . '</a>' : 'SYSTEM';
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($message['subject']) ?></h1>
    <a href="<?= $received ? '/mail' : '/mail/outbox' ?>" class="link">&larr; <?= $received ? 'Inbox' : 'Sent' ?></a>
</div>

<article class="card space-y-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        From <?= $person($message['sender_id'] === null ? null : (int) $message['sender_id'], $message['sender_username']) ?>
        to <?= $person((int) $message['recipient_id'], $message['recipient_username']) ?>
        · <?= e(date('F j, Y, g:i a', strtotime($message['created_at']))) ?>
    </p>
    <div class="whitespace-pre-line"><?= e($message['body']) ?></div>
</article>

<?php if ($received): ?>
    <div class="mt-4 flex flex-wrap gap-3">
        <?php if ($message['sender_id'] !== null): ?>
            <a href="/mail/compose?reply=<?= (int) $message['id'] ?>" class="btn btn-primary">Reply</a>
        <?php endif; ?>
        <form method="post" action="/mail/<?= (int) $message['id'] ?>/delete">
            <?= csrf_field() ?>
            <?= $this->component('button', ['label' => 'Delete', 'type' => 'submit', 'variant' => 'danger']) ?>
        </form>
    </div>
<?php endif; ?>
