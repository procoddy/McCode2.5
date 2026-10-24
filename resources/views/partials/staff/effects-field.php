<?php
/**
 * Item effects editor (legacy staff_items.php effect1..3 radio/select rows).
 *
 * @var string $name
 * @var array $field
 * @var list<array{stat?: string, direction?: string, type?: string, amount?: int|string}> $rows
 */
use App\Modules\Inventory\Models\ItemEffect;

$rows = array_values(array_filter($rows, 'is_array'));
$fieldErrors = $errors[$name] ?? [];
$select = function (string $input, array $options, ?string $value, string $label): string {
    $html = '<label class="sr-only" for="' . e($input) . '">' . e($label) . '</label>'
        . '<select class="input" id="' . e($input) . '" name="' . e($input) . '">';
    foreach ($options as $optionValue => $optionLabel) {
        $html .= '<option value="' . e($optionValue) . '"' . ((string) $optionValue === (string) $value ? ' selected' : '') . '>' . e($optionLabel) . '</option>';
    }

    return $html . '</select>';
};
?>
<fieldset class="space-y-3">
    <legend class="mb-1 text-sm font-medium"><?= e($field['label']) ?></legend>
    <?php for ($i = 0; $i < ($field['max'] ?? 3); $i++): ?>
        <?php $row = $rows[$i] ?? []; $prefix = "{$name}[{$i}]"; ?>
        <div class="grid gap-2 rounded-md border border-gray-200 p-3 sm:grid-cols-[1fr_auto_6rem_auto] dark:border-gray-800">
            <?= $select("{$prefix}[stat]", ['' => 'Effect ' . ($i + 1) . ': off'] + ItemEffect::STATS, $row['stat'] ?? '', 'Stat') ?>
            <?= $select("{$prefix}[direction]", ['increase' => 'Increase', 'decrease' => 'Decrease'], $row['direction'] ?? 'increase', 'Direction') ?>
            <label class="sr-only" for="<?= e("{$prefix}[amount]") ?>">Amount</label>
            <input type="number" min="1" class="input" id="<?= e("{$prefix}[amount]") ?>" name="<?= e("{$prefix}[amount]") ?>" value="<?= e($row['amount'] ?? '') ?>" placeholder="Amount">
            <?= $select("{$prefix}[type]", ['figure' => 'Value', 'percent' => 'Percent'], $row['type'] ?? 'figure', 'Amount type') ?>
        </div>
    <?php endfor; ?>
    <?php if ($fieldErrors): ?>
        <p class="text-sm text-red-600 dark:text-red-400"><?= e($fieldErrors[0]) ?></p>
    <?php elseif (!empty($field['hint'])): ?>
        <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($field['hint']) ?></p>
    <?php endif; ?>
</fieldset>
