<?php
/**
 * Swapped over itself by the forms below (hx-target="#bank-panel").
 *
 * @var App\Modules\Banking\Models\BankAccountType $account
 * @var App\Modules\Players\Models\Player $player
 * @var string|null $message
 * @var array $input  Submitted values after a failed HTMX post
 * @var bool $oob     Also update the top bar
 */
$balance = $player->balance($account->column);
$input ??= [];
$fee = fn (float $percent, int $max) => $percent > 0
    ? "{$percent}% (max " . App\Support\Money::of($max)->format() . ')'
    : 'none';
$hx = fn (string $action) => 'hx-post="' . e($account->url . '/' . $action) . '" hx-target="#bank-panel" hx-swap="outerHTML"';
?>
<section id="bank-panel" class="space-y-6">
    <?php if (!empty($message)): ?>
        <?= $this->component('alert', ['message' => $message, 'type' => 'success']) ?>
    <?php endif; ?>

    <?php if ($balance === null): ?>
        <form method="post" action="<?= e($account->url) ?>/open" class="card space-y-4" <?= $hx('open') ?>>
            <?= csrf_field() ?>
            <p>Open a <?= e($account->name) ?> account today for just
                <strong><?= e(App\Support\Money::of($account->cost)->format()) ?></strong>.</p>
            <?php if (!empty($errors['account'])): ?>
                <?= $this->component('alert', ['message' => $errors['account'][0], 'type' => 'error']) ?>
            <?php endif; ?>
            <?= $this->component('button', ['label' => 'Yes, sign me up', 'type' => 'submit']) ?>
        </form>
    <?php else: ?>
        <div class="card">
            <p class="text-sm text-gray-500 dark:text-gray-400">Balance</p>
            <p class="mt-1 text-3xl font-semibold"><?= e(App\Support\Money::of($balance)->format()) ?></p>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Your balance grows by <?= e($account->dailyInterestPercent) ?>% at the end of each day.
            </p>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <form method="post" action="<?= e($account->url) ?>/deposit" class="card space-y-4" <?= $hx('deposit') ?>>
                <?= csrf_field() ?>
                <h2 class="font-semibold">Deposit</h2>
                <?= $this->component('form-field', [
                    'name' => 'deposit',
                    'label' => 'Amount',
                    'type' => 'number',
                    'value' => $input['deposit'] ?? old('deposit', $player->money->amount()),
                    'hint' => 'Fee: ' . $fee($account->depositFeePercent, $account->depositMaxFee) . ', rounded up.',
                ]) ?>
                <?= $this->component('button', ['label' => 'Deposit', 'type' => 'submit']) ?>
            </form>

            <form method="post" action="<?= e($account->url) ?>/withdraw" class="card space-y-4" <?= $hx('withdraw') ?>>
                <?= csrf_field() ?>
                <h2 class="font-semibold">Withdraw</h2>
                <?= $this->component('form-field', [
                    'name' => 'withdraw',
                    'label' => 'Amount',
                    'type' => 'number',
                    'value' => $input['withdraw'] ?? old('withdraw', $balance),
                    'hint' => 'Fee: ' . $fee($account->withdrawFeePercent, $account->withdrawMaxFee) . '.',
                ]) ?>
                <?= $this->component('button', ['label' => 'Withdraw', 'type' => 'submit', 'variant' => 'secondary']) ?>
            </form>
        </div>
    <?php endif; ?>
</section>
<?php if (!empty($oob)): ?>
    <?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
<?php endif; ?>
