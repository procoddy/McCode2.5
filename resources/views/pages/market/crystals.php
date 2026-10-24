<?php
/**
 * @var list<array<string, mixed>> $listings
 * @var App\Modules\Players\Models\Player $player
 */
use App\Support\Money;

$name = config('game.crystals_name');
$this->set('title', "{$name} Market");
?>
<h1 class="mb-6 text-2xl font-semibold"><?= e($name) ?> Market</h1>

<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<?php if ($listings === []): ?>
    <div class="card mb-6 text-center text-gray-500 dark:text-gray-400">No <?= e(strtolower($name)) ?> are for sale right now.</div>
<?php else: ?>
    <div class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-2 font-semibold">Seller</th>
                    <th class="px-4 py-2 font-semibold">Qty</th>
                    <th class="px-4 py-2 font-semibold">Price each</th>
                    <th class="px-4 py-2 font-semibold">Total</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($listings as $l): ?>
                    <tr>
                        <td class="px-4 py-2"><a href="/players/<?= (int) $l['seller_id'] ?>" class="link"><?= e($l['seller']) ?></a></td>
                        <td class="px-4 py-2"><?= number_format((int) $l['quantity']) ?></td>
                        <td class="px-4 py-2"><?= e(Money::of((int) $l['price_each'])->format()) ?></td>
                        <td class="px-4 py-2"><?= e(Money::of((int) $l['price_each'] * (int) $l['quantity'])->format()) ?></td>
                        <td class="px-4 py-2">
                            <?php if ((int) $l['seller_id'] === $player->id): ?>
                                <form method="post" action="/crystal-market/<?= (int) $l['id'] ?>/remove" class="text-right">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="link cursor-pointer">Remove</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/crystal-market/<?= (int) $l['id'] ?>/buy" class="flex items-center justify-end gap-2">
                                    <?= csrf_field() ?>
                                    <label class="sr-only" for="buy-<?= (int) $l['id'] ?>">Quantity</label>
                                    <input id="buy-<?= (int) $l['id'] ?>" type="number" name="quantity" value="<?= (int) $l['quantity'] ?>" min="1" max="<?= (int) $l['quantity'] ?>" class="input w-24 py-1">
                                    <button type="submit" class="btn btn-secondary px-3 py-1 text-xs">Buy</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<form method="post" action="/crystal-market" class="card max-w-xl space-y-4">
    <?= csrf_field() ?>
    <h2 class="font-semibold">Sell <?= e(strtolower($name)) ?></h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">You have <?= number_format($player->crystals) ?>.</p>
    <div class="grid gap-4 sm:grid-cols-2">
        <?= $this->component('form-field', ['name' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'value' => old('quantity'), 'errors' => []]) ?>
        <?= $this->component('form-field', ['name' => 'price_each', 'label' => 'Price each', 'type' => 'number', 'value' => old('price_each'), 'errors' => []]) ?>
    </div>
    <?= $this->component('button', ['label' => 'Add listing', 'type' => 'submit']) ?>
</form>
