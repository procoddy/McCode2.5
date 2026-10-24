<?php
/**
 * @var array<string, mixed> $player
 * @var list<App\Modules\Inventory\Models\InventoryEntry> $entries
 * @var array<string, App\Modules\Inventory\Models\Item|null> $equipped
 * @var callable(string ...): bool $staffCan
 */
use App\Modules\Inventory\Models\EquipmentSlot;

$this->set('title', "{$player['username']}'s inventory");
$canRemove = ($staffCan ?? fn () => false)('manage_items', 'credit_item');
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($player['username']) ?>'s inventory</h1>
    <a href="/admin/users/<?= (int) $player['id'] ?>/edit" class="link">&larr; Edit player</a>
</div>
<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
    Equipped:
    <?= e(implode(' · ', array_map(fn (EquipmentSlot $slot) => $slot->label() . ': ' . ($equipped[$slot->value]?->name ?? 'none'), EquipmentSlot::cases()))) ?>
</p>

<?= $this->partial('staff-table', [
    'headings' => ['Item', 'Type', 'Quantity', ''],
    'rows' => array_map(fn ($entry) => [
        $entry->item->name,
        $entry->item->typeName ?? 'Other',
        number_format($entry->quantity),
        ['html' => $canRemove
            ? '<form method="post" action="/admin/users/' . (int) $player['id'] . '/inventory/' . $entry->item->id . '/remove" class="flex items-center justify-end gap-2" hx-confirm="Remove from this inventory?">'
                . csrf_field()
                . '<label class="sr-only" for="rm-' . $entry->item->id . '">Quantity</label>'
                . '<input id="rm-' . $entry->item->id . '" type="number" name="quantity" min="1" max="' . $entry->quantity . '" value="1" class="input w-20 py-1">'
                . '<button class="link cursor-pointer text-red-600" type="submit">Remove</button></form>'
            : ''],
    ], $entries),
    'empty' => 'This player has no items.',
]) ?>
