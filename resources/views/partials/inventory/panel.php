<?php
/**
 * @var App\Modules\Players\Models\Player $player
 * @var list<App\Modules\Inventory\Models\InventoryEntry> $entries
 * @var array<string, App\Modules\Inventory\Models\Item|null> $equipped
 * @var string|null $message
 * @var bool $oob
 */
use App\Modules\Inventory\Models\EquipmentSlot;

$hx = fn (string $url) => 'hx-post="' . e($url) . '" hx-target="#inventory-panel" hx-swap="outerHTML"';
$lastType = false;
?>
<section id="inventory-panel" class="space-y-6">
    <?php if (!empty($message)): ?>
        <?= $this->component('alert', ['message' => $message, 'type' => 'success']) ?>
    <?php endif; ?>
    <?php if (!empty($errors['item'])): ?>
        <?= $this->component('alert', ['message' => $errors['item'][0], 'type' => 'error']) ?>
    <?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-3">
        <?php foreach (EquipmentSlot::cases() as $slot): ?>
            <?php $item = $equipped[$slot->value] ?? null; ?>
            <div class="card">
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($slot->label()) ?></p>
                <?php if ($item === null): ?>
                    <p class="mt-1 font-medium text-gray-400">None equipped</p>
                <?php else: ?>
                    <p class="mt-1 font-medium"><a href="/inventory/<?= $item->id ?>" class="link"><?= e($item->name) ?></a></p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= $slot === EquipmentSlot::Armor ? 'Defense ' . $item->armor : 'Power ' . $item->weapon ?>
                    </p>
                    <form method="post" action="/inventory/unequip/<?= $slot->value ?>" class="mt-2" <?= $hx("/inventory/unequip/{$slot->value}") ?>>
                        <?= csrf_field() ?>
                        <button type="submit" class="link cursor-pointer text-sm">Unequip</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($entries === []): ?>
        <div class="card text-center text-gray-500 dark:text-gray-400">
            You have no items. <a href="/shops" class="link">Visit the shops</a>.
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                    <tr>
                        <th class="px-4 py-2 font-semibold">Item</th>
                        <th class="px-4 py-2 font-semibold">Sell value</th>
                        <th class="px-4 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    <?php foreach ($entries as $entry): ?>
                        <?php $item = $entry->item; ?>
                        <?php if ($item->typeName !== $lastType): $lastType = $item->typeName; ?>
                            <tr class="bg-gray-50/60 dark:bg-gray-800/30">
                                <th colspan="3" class="px-4 py-1.5 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase"><?= e($item->typeName ?? 'Other') ?></th>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="px-4 py-2">
                                <a href="/inventory/<?= $item->id ?>" class="link"><?= e($item->name) ?></a>
                                <?php if ($entry->quantity > 1): ?><span class="text-gray-500">&times;<?= number_format($entry->quantity) ?></span><?php endif; ?>
                            </td>
                            <td class="px-4 py-2">
                                <?= e($item->sellValue()->format()) ?>
                                <?php if ($entry->quantity > 1): ?><span class="text-gray-500">(<?= e($item->sellValue($entry->quantity)->format()) ?> total)</span><?php endif; ?>
                            </td>
                            <td class="px-4 py-2">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <?php if ($item->isUsable()): ?>
                                        <form method="post" action="/inventory/<?= $item->id ?>/use" <?= $hx("/inventory/{$item->id}/use") ?>>
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-secondary px-2 py-1 text-xs">Use</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php foreach (EquipmentSlot::cases() as $slot): ?>
                                        <?php if ($slot->accepts($item)): ?>
                                            <form method="post" action="/inventory/<?= $item->id ?>/equip" <?= $hx("/inventory/{$item->id}/equip") ?>>
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="slot" value="<?= $slot->value ?>">
                                                <button type="submit" class="btn btn-secondary px-2 py-1 text-xs">Equip <?= e(strtolower($slot === EquipmentSlot::Armor ? 'armor' : $slot->value)) ?></button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <a href="/inventory/<?= $item->id ?>" class="btn btn-secondary px-2 py-1 text-xs">Sell / send</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php if (!empty($oob)): ?>
    <?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
<?php endif; ?>
