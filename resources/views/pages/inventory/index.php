<?php $this->set('title', 'Inventory'); ?>
<h1 class="mb-6 text-2xl font-semibold">Inventory</h1>

<?= $this->partial('inventory.panel', ['player' => $player, 'entries' => $entries, 'equipped' => $equipped]) ?>
