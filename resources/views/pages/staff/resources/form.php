<?php
/**
 * @var App\Modules\Staff\Resources\ResourceDefinition $def
 * @var int|null $id
 * @var array<string, mixed> $values
 * @var array<string, array<int, string>> $options
 */
$editing = $id !== null;
$this->set('title', ($editing ? 'Edit ' : 'New ') . $def->singular);
$hasOld = app(App\Core\Session::class)->has('_old_input');
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= $editing ? 'Edit ' . e($def->singular) . " #{$id}" : 'New ' . e($def->singular) ?></h1>
    <a href="<?= e($def->url()) ?>" class="link">&larr; All <?= e(strtolower($def->title)) ?></a>
</div>

<form method="post" action="<?= e($editing ? $def->url((string) $id) : $def->url()) ?>" class="card max-w-2xl space-y-4" novalidate>
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>

    <?php foreach ($def->fields as $name => $field): ?>
        <?php if ($field['type'] === 'effects'): ?>
            <?= $this->partial('staff.effects-field', [
                'name' => $name,
                'field' => $field,
                'rows' => $hasOld ? (array) old($name, []) : array_map(fn ($e) => $e->toArray(), App\Modules\Inventory\Models\Item::decodeEffects($values[$name] ?? null)),
            ]) ?>
            <?php continue; ?>
        <?php endif; ?>
        <?php
        $value = $hasOld ? old($name, $field['type'] === 'pivot' ? [] : '') : ($values[$name] ?? null);
        $props = ['name' => $name, 'label' => $field['label'], 'hint' => $field['hint'] ?? null];
        $props += match ($field['type']) {
            'text' => ['type' => 'textarea', 'value' => $value],
            'int', 'money', 'user' => ['type' => 'number', 'value' => $value],
            'bool' => ['type' => 'checkbox', 'checked' => (bool) $value],
            'choice' => ['type' => 'select', 'value' => $value, 'options' => $field['options']],
            'select' => ['type' => 'select', 'value' => $value, 'options' => $options[$name] ?? [], 'nullable' => $field['nullable'] ?? false],
            'pivot' => ['type' => 'checkboxes', 'options' => $options[$name] ?? [], 'selected' => array_filter((array) $value)],
            default => ['type' => 'text', 'value' => $value],
        };
        ?>
        <?= $this->component('form-field', $props) ?>
    <?php endforeach; ?>

    <div class="flex items-center gap-4 pt-2">
        <?= $this->component('button', ['label' => $editing ? 'Save changes' : 'Create', 'type' => 'submit']) ?>
        <a href="<?= e($def->url()) ?>" class="link">Cancel</a>
    </div>
</form>

<?php if ($editing): ?>
    <form method="post" action="<?= e($def->url((string) $id)) ?>" class="mt-6"
          hx-confirm="Delete this <?= e($def->singular) ?>? This cannot be undone.">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <?= $this->component('button', ['label' => 'Delete ' . $def->singular, 'type' => 'submit', 'variant' => 'danger']) ?>
    </form>
<?php endif; ?>
