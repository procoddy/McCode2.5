<?php
/**
 * @var int|string $to
 * @var string $subject
 * @var string|null $banReason
 */
$this->set('title', 'Compose');
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Compose a message</h1>
    <a href="/mail" class="link">&larr; Mailbox</a>
</div>

<?php if ($banReason !== null): ?>
    <?= $this->component('alert', ['message' => $banReason, 'type' => 'error']) ?>
<?php else: ?>
    <form method="post" action="/mail" class="card max-w-2xl space-y-4">
        <?= csrf_field() ?>
        <?= $this->component('form-field', ['name' => 'to', 'label' => 'To (player ID)', 'type' => 'number', 'value' => old('to', $to)]) ?>
        <?= $this->component('form-field', ['name' => 'subject', 'label' => 'Subject', 'value' => old('subject', $subject), 'attrs' => ['maxlength' => '255']]) ?>
        <?= $this->component('form-field', ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'value' => old('body'), 'attrs' => ['maxlength' => '5000']]) ?>
        <?= $this->component('button', ['label' => 'Send', 'type' => 'submit']) ?>
    </form>
<?php endif; ?>
