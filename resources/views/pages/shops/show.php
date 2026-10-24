<?php $this->set('title', $shop['name']); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($shop['name']) ?></h1>
    <a href="/shops" class="link">&larr; All shops</a>
</div>

<?= $this->partial('shops.panel', ['shop' => $shop, 'stock' => $stock, 'player' => $player]) ?>
