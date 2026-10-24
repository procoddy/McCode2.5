<?php
/**
 * @var App\Modules\CrystalTemple\Services\CrystalTempleService $temple
 * @var App\Modules\Players\Models\Player $player
 * @var string|null $message
 * @var array $input
 * @var bool $oob
 */
$name = config('game.crystals_name');
$input ??= [];
$hx = fn (string $action) => 'hx-post="/crystal-temple/' . $action . '" hx-target="#temple-panel" hx-swap="outerHTML"';
?>
<section id="temple-panel" class="space-y-6">
    <?php if (!empty($message)): ?>
        <?= $this->component('alert', ['message' => $message, 'type' => 'success']) ?>
    <?php endif; ?>

    <p>You have <strong><?= e(number_format($player->crystals)) ?></strong> <?= e(strtolower($name)) ?>. What would you like to spend them on?</p>

    <div class="grid gap-6 lg:grid-cols-3">
        <form method="post" action="/crystal-temple/refill" class="card space-y-4" <?= $hx('refill') ?>>
            <?= csrf_field() ?>
            <h2 class="font-semibold">Energy refill</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                Refill your energy to <?= $player->maxEnergy ?> for <?= e($temple->refillPrice()) ?> <?= e(strtolower($name)) ?>.
                You have <?= $player->energy ?>/<?= $player->maxEnergy ?>.
            </p>
            <?php if (!empty($errors['refill'])): ?>
                <p class="text-sm text-red-600 dark:text-red-400"><?= e($errors['refill'][0]) ?></p>
            <?php endif; ?>
            <?= $this->component('button', ['label' => 'Refill energy', 'type' => 'submit']) ?>
        </form>

        <form method="post" action="/crystal-temple/iq" class="card space-y-4" <?= $hx('iq') ?>>
            <?= csrf_field() ?>
            <h2 class="font-semibold">IQ</h2>
            <?= $this->component('form-field', [
                'name' => 'iq_crystals',
                'label' => $name . ' to trade',
                'type' => 'number',
                'value' => $input['iq_crystals'] ?? old('iq_crystals'),
                'hint' => "1 = {$temple->iqPerCrystal()} IQ",
            ]) ?>
            <?= $this->component('button', ['label' => 'Swap for IQ', 'type' => 'submit']) ?>
        </form>

        <form method="post" action="/crystal-temple/money" class="card space-y-4" <?= $hx('money') ?>>
            <?= csrf_field() ?>
            <h2 class="font-semibold">Money</h2>
            <?= $this->component('form-field', [
                'name' => 'money_crystals',
                'label' => $name . ' to trade',
                'type' => 'number',
                'value' => $input['money_crystals'] ?? old('money_crystals'),
                'hint' => '1 = ' . App\Support\Money::of($temple->moneyPerCrystal())->format(),
            ]) ?>
            <?= $this->component('button', ['label' => 'Swap for money', 'type' => 'submit']) ?>
        </form>
    </div>
</section>
<?php if (!empty($oob)): ?>
    <?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
<?php endif; ?>
