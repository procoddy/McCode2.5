<?php
/**
 * @var App\Modules\Staff\Resources\ResourceDefinition $def
 * @var App\Support\Pagination $rows
 * @var array<string, array<int, string>> $options
 */
$this->set('title', $def->title);
$display = function (string $column, array $row) use ($def, $options): string {
    $field = $def->fields[$column] ?? ['type' => 'string'];
    $value = $row[$column] ?? null;

    return match ($field['type']) {
        'money' => App\Support\Money::of((int) $value)->format(),
        'bool' => $value ? 'Yes' : 'No',
        'select' => $value === null ? '—' : ($options[$column][(int) $value] ?? "#{$value}"),
        default => App\Support\Str::limit((string) $value, 60),
    };
};
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($def->title) ?></h1>
    <a href="<?= e($def->url('create')) ?>" class="btn btn-primary">New <?= e($def->singular) ?></a>
</div>

<?= $this->partial('staff-table', [
    'headings' => ['ID', ...array_map(fn ($c) => $def->label($c), $def->columns), ''],
    'rows' => array_map(fn ($row) => [
        $row['id'],
        ...array_map(fn ($c) => $display($c, $row), $def->columns),
        ['html' => '<a class="link" href="' . e($def->url("{$row['id']}/edit")) . '">Edit</a>'],
    ], $rows->items),
    'empty' => 'No ' . strtolower($def->title) . ' yet.',
]) ?>

<?= $this->component('pagination', ['paginator' => $rows, 'url' => $def->url()]) ?>
