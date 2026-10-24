<?php
/** @var array<string, mixed> $post */
$this->set('title', 'Edit post');
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Edit post in “<?= e($post['topic_title']) ?>”</h1>
    <a href="/forums/topics/<?= (int) $post['topic_id'] ?>" class="link">&larr; Back to topic</a>
</div>
<form method="post" action="/forums/posts/<?= (int) $post['id'] ?>" class="card max-w-2xl space-y-4">
    <?= csrf_field() ?>
    <?= $this->component('form-field', ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'value' => old('body', $post['body'])]) ?>
    <?= $this->component('button', ['label' => 'Save', 'type' => 'submit']) ?>
</form>
