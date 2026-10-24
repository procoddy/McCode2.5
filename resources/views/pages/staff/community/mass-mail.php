<?php
/** @var array<int, string> $roles */
$this->set('title', 'Mass mail');
?>
<h1 class="mb-2 text-2xl font-semibold">Mass mail</h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Send a message to every player (NPCs excluded), or to everyone holding one staff role.</p>
<form method="post" action="/admin/mass-mail" class="card max-w-2xl space-y-4" hx-confirm="Send this mail?">
    <?= csrf_field() ?>
    <?= $this->component('form-field', ['name' => 'role_id', 'label' => 'Send to', 'type' => 'select', 'value' => old('role_id', 0), 'options' => [0 => 'Every player'] + array_map(fn ($name) => "Staff role: {$name}", $roles)]) ?>
    <?= $this->component('form-field', ['name' => 'subject', 'label' => 'Subject', 'value' => old('subject')]) ?>
    <?= $this->component('form-field', ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'value' => old('body')]) ?>
    <?= $this->component('button', ['label' => 'Send', 'type' => 'submit']) ?>
</form>
