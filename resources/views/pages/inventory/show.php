<?php $this->set('title', $item->name); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($item->name) ?></h1>
    <a href="/inventory" class="link">&larr; Inventory</a>
</div>

<?= $this->partial('inventory.item-panel', ['item' => $item, 'owned' => $owned, 'player' => $player]) ?>
