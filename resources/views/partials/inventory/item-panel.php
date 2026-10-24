<?php
/**
 * Legacy iteminfo.php, with the itemsell.php / itemsend.php / imadd.php forms.
 *
 * @var App\Modules\Inventory\Models\Item $item
 * @var int $owned
 * @var App\Modules\Players\Models\Player $player
 * @var string|null $message
 * @var array $input
 * @var bool $oob
 */
use App\Support\Money;

$input ??= [];
$hx = fn (string $url, string $target = '#item-panel') => 'hx-post="' . e($url) . '" hx-target="' . $target . '" hx-swap="outerHTML"';
$rows = [
    'Type' => $item->typeName ?? 'Other',
    'Shop price' => $item->buyable ? Money::of($item->buyPrice)->format() : 'Not sold in shops',
    'Sell value' => $item->sellValue()->format(),
    ...($item->isWeapon() ? ['Weapon power' => number_format($item->weapon)] : []),
    ...($item->isArmor() ? ['Armor defense' => number_format($item->armor)] : []),
    'You have' => number_format($owned),
];
?>
<section id="item-panel" class="space-y-6">
    <?php if (!empty($message)): ?>
        <?= $this->component('alert', ['message' => $message, 'type' => 'success']) ?>
    <?php endif; ?>

    <div class="card space-y-4">
        <?php if ($item->description !== ''): ?>
            <p class="text-gray-700 dark:text-gray-300"><?= nl2br(e($item->description)) ?></p>
        <?php endif; ?>
        <dl class="grid grid-cols-[max-content_1fr] gap-x-6 gap-y-1.5 text-sm">
            <?php foreach ($rows as $label => $value): ?>
                <dt class="text-gray-500 dark:text-gray-400"><?= e($label) ?></dt>
                <dd><?= e($value) ?></dd>
            <?php endforeach; ?>
        </dl>
        <?php if ($item->isUsable()): ?>
            <div>
                <h2 class="mb-1 text-sm font-semibold">When used</h2>
                <ul class="list-inside list-disc text-sm text-gray-700 dark:text-gray-300">
                    <?php foreach ($item->effects as $effect): ?>
                        <li><?= e($effect->describe()) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($owned > 0): ?>
        <div class="grid gap-6 lg:grid-cols-3">
            <form method="post" action="/inventory/<?= $item->id ?>/sell" class="card space-y-4" <?= $hx("/inventory/{$item->id}/sell") ?>>
                <?= csrf_field() ?>
                <h2 class="font-semibold">Sell to the shop</h2>
                <?= $this->component('form-field', [
                    'name' => 'sell_quantity', 'label' => 'Quantity', 'type' => 'number',
                    'value' => $input['sell_quantity'] ?? old('sell_quantity', '1'),
                    'hint' => $item->sellValue()->format() . ' each',
                ]) ?>
                <?= $this->component('button', ['label' => 'Sell', 'type' => 'submit']) ?>
            </form>

            <form method="post" action="/inventory/<?= $item->id ?>/send" class="card space-y-4" <?= $hx("/inventory/{$item->id}/send") ?>>
                <?= csrf_field() ?>
                <h2 class="font-semibold">Send to a player</h2>
                <?= $this->component('form-field', [
                    'name' => 'user_id', 'label' => 'Player ID', 'type' => 'number',
                    'value' => $input['user_id'] ?? old('user_id'),
                ]) ?>
                <?= $this->component('form-field', [
                    'name' => 'send_quantity', 'label' => 'Quantity', 'type' => 'number',
                    'value' => $input['send_quantity'] ?? old('send_quantity', '1'),
                ]) ?>
                <?= $this->component('button', ['label' => 'Send', 'type' => 'submit']) ?>
            </form>

            <form method="post" action="/item-market" class="card space-y-4">
                <?= csrf_field() ?>
                <h2 class="font-semibold">List on the item market</h2>
                <input type="hidden" name="item_id" value="<?= $item->id ?>">
                <?= $this->component('form-field', ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'value' => old('quantity', '1')]) ?>
                <?= $this->component('form-field', ['name' => 'price', 'label' => 'Price each', 'type' => 'number', 'value' => old('price')]) ?>
                <?= $this->component('form-field', [
                    'name' => 'currency', 'label' => 'Currency', 'type' => 'select', 'value' => old('currency', 'money'),
                    'options' => ['money' => 'Money', 'crystals' => config('game.crystals_name')],
                ]) ?>
                <?= $this->component('button', ['label' => 'Add listing', 'type' => 'submit']) ?>
            </form>
        </div>
    <?php endif; ?>
</section>
<?php if (!empty($oob)): ?>
    <?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
<?php endif; ?>
