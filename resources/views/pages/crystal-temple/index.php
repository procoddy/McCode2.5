<?php $this->set('title', config('game.crystals_name') . ' Temple'); ?>
<h1 class="mb-6 text-2xl font-semibold"><?= e(config('game.crystals_name')) ?> Temple</h1>

<?= $this->partial('crystal-temple.panel', ['temple' => $temple, 'player' => $player]) ?>
