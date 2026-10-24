<?php $this->set('title', 'Announcements'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Announcements</h1>
    <a href="/admin" class="link">&larr; Staff Panel</a>
</div>

<?= $this->partial('announcements.admin-panel', [
    'announcements' => $announcements,
    'errors' => $errors ?? [],
    'body' => '',
    'message' => null,
]) ?>
