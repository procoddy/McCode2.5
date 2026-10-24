<?php
/**
 * @var string       $name
 * @var string       $label
 * @var string       $type         text|email|password|number|textarea|checkbox|select|checkboxes (default text)
 * @var array        $options      select/checkboxes: value => label
 * @var bool         $nullable     select: offer an empty "None" choice
 * @var list<int|string> $selected checkboxes: checked values
 * @var string|null  $value
 * @var bool         $checked      checkbox only
 * @var list<string> $errors       Optional; defaults to the page's errors for $name
 * @var string|null  $hint         Help text shown under the input
 * @var string|null  $autocomplete
 * @var bool         $autofocus
 * @var array        $attrs        Extra input attributes, e.g. ['hx-post' => '/register/check']
 */
$id = 'field-' . $name;
$type ??= 'text';
// $errors is either this field's list of messages or, when the caller passes
// none, the page-wide validation errors keyed by field name.
$errors ??= [];
$fieldErrors = array_is_list($errors) ? $errors : ($errors[$name] ?? []);
$describedBy = $fieldErrors ? "{$id}-error" : (!empty($hint) ? "{$id}-hint" : '');

$attributes = 'id="' . e($id) . '" name="' . e($name) . '"';
if (!empty($autocomplete)) {
    $attributes .= ' autocomplete="' . e($autocomplete) . '"';
}
if (!empty($autofocus)) {
    $attributes .= ' autofocus';
}
if ($fieldErrors) {
    $attributes .= ' aria-invalid="true"';
}
if ($describedBy !== '') {
    $attributes .= ' aria-describedby="' . e($describedBy) . '"';
}
foreach ($attrs ?? [] as $attr => $attrValue) {
    $attributes .= ' ' . e($attr) . '="' . e($attrValue) . '"';
}
$inputClass = 'input' . ($fieldErrors ? ' input-invalid' : '');
?>
<div>
    <?php if ($type === 'checkbox'): ?>
        <?php /* Unchecked boxes are not submitted, so the hidden 0 is sent instead. */ ?>
        <input type="hidden" name="<?= e($name) ?>" value="0">
        <label for="<?= e($id) ?>" class="flex items-center gap-2 text-sm font-medium">
            <input type="checkbox" value="1" class="size-4 accent-brand-600" <?= $attributes ?><?= !empty($checked) ? ' checked' : '' ?>>
            <?= e($label) ?>
        </label>
    <?php else: ?>
        <label for="<?= e($id) ?>" class="mb-1 block text-sm font-medium"><?= e($label) ?></label>
        <?php if ($type === 'select'): ?>
            <select class="<?= $inputClass ?>" <?= $attributes ?>>
                <?php if (!empty($nullable)): ?><option value="">None</option><?php endif; ?>
                <?php foreach ($options ?? [] as $optionValue => $optionLabel): ?>
                    <option value="<?= e($optionValue) ?>"<?= (string) $optionValue === (string) ($value ?? '') ? ' selected' : '' ?>><?= e($optionLabel) ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($type === 'checkboxes'): ?>
            <?php /* The hidden input lets "nothing selected" clear the list. */ ?>
            <input type="hidden" name="<?= e($name) ?>[]" value="">
            <div class="grid max-h-64 gap-1 overflow-y-auto rounded-md border border-gray-300 p-3 sm:grid-cols-2 dark:border-gray-700" id="<?= e($id) ?>">
                <?php foreach ($options ?? [] as $optionValue => $optionLabel): ?>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" class="size-4 accent-brand-600" name="<?= e($name) ?>[]" value="<?= e($optionValue) ?>"
                            <?= in_array((string) $optionValue, array_map('strval', $selected ?? []), true) ? 'checked' : '' ?>>
                        <?= e($optionLabel) ?>
                    </label>
                <?php endforeach; ?>
                <?php if (empty($options)): ?><p class="text-sm text-gray-500">Nothing to choose from yet.</p><?php endif; ?>
            </div>
        <?php elseif ($type === 'textarea'): ?>
            <textarea rows="5" class="<?= $inputClass ?>" <?= $attributes ?>><?= e($value ?? '') ?></textarea>
        <?php else: ?>
            <input type="<?= e($type) ?>" class="<?= $inputClass ?>" <?= $attributes ?><?php if ($type !== 'password'): ?> value="<?= e($value ?? '') ?>"<?php endif; ?>>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($fieldErrors): ?>
        <p class="mt-1 text-sm text-red-600 dark:text-red-400" id="<?= e($id) ?>-error"><?= e($fieldErrors[0]) ?></p>
    <?php elseif (!empty($hint)): ?>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400" id="<?= e($id) ?>-hint"><?= e($hint) ?></p>
    <?php endif; ?>
</div>
