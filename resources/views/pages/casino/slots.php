<?php $this->set('title', 'Slots'); ?>
<h1 class="mb-6 text-2xl font-semibold">Slots</h1>
<?= $this->partial('casino.slots-panel', ['player' => $player, 'maxBet' => $maxBet, 'result' => $result, 'bet' => $result['bet'] ?? null]) ?>
