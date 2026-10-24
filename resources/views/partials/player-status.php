<?php
/**
 * Player summary in the top bar. Panels re-send it with $oob = true so HTMX
 * updates the numbers after a deposit, crime, etc. (hx-swap-oob).
 *
 * @var App\Modules\Players\Models\Player|null $player
 * @var bool $oob
 */
if (!isset($player)) {
    return;
}
?>
<span id="player-status" <?= !empty($oob) ? 'hx-swap-oob="true"' : '' ?>
      class="hidden flex-wrap gap-x-3 text-gray-500 sm:flex dark:text-gray-400">
    <span><?= e($player->username) ?> · Lvl <?= e($player->level) ?></span>
    <span title="Money"><?= e($player->money->format()) ?></span>
    <span title="<?= e(config('game.crystals_name')) ?>"><?= e(number_format($player->crystals)) ?> <?= e(strtolower(config('game.crystals_name'))) ?></span>
    <span title="Energy">E <?= $player->energy ?>/<?= $player->maxEnergy ?></span>
    <span title="Brave">B <?= $player->brave ?>/<?= $player->maxBrave ?></span>
</span>
