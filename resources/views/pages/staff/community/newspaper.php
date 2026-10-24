<?php
/** @var string $body */
$this->set('title', 'Newspaper');
?>
<h1 class="mb-6 text-2xl font-semibold">Edit the newspaper</h1>
<form method="post" action="/admin/newspaper" class="card max-w-3xl space-y-4">
    <?= csrf_field() ?>
    <?= $this->component('form-field', ['name' => 'body', 'label' => 'Content (plain text; shown to players at /newspaper)', 'type' => 'textarea', 'value' => old('body', $body)]) ?>
    <?= $this->component('button', ['label' => 'Save', 'type' => 'submit']) ?>
</form>
