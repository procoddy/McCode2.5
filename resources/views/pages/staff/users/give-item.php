<?php
/** @var array<int, string> $items */
$this->set('title', 'Give item');
?>
<h1 class="mb-2 text-2xl font-semibold">Give item</h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Put items straight into a player's inventory. They get an event telling them.</p>

<?php if ($items === []): ?>
    <div class="card max-w-md text-gray-500">No items exist yet. <a href="/admin/data/items/create" class="link">Create one</a>.</div>
<?php else: ?>
    <form method="post" action="/admin/users/give-item" class="card max-w-md space-y-4">
        <?= csrf_field() ?>
        <?= $this->component('form-field', ['name' => 'username', 'label' => 'Username', 'value' => old('username')]) ?>
        <?= $this->component('form-field', ['name' => 'item_id', 'label' => 'Item', 'type' => 'select', 'options' => $items, 'value' => old('item_id')]) ?>
        <?= $this->component('form-field', ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'value' => old('quantity', 1)]) ?>
        <?= $this->component('button', ['label' => 'Give item', 'type' => 'submit']) ?>
    </form>
<?php endif; ?>
