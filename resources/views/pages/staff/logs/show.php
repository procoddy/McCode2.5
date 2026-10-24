<?php
/**
 * @var string $key
 * @var array $log
 * @var App\Support\Pagination $rows
 */
$this->set('title', $log['title'] . ' log');
$format = fn (mixed $value, ?string $type) => match ($type) {
    'money' => App\Support\Money::of((int) $value)->format(),
    'datetime' => $value ? date('M j Y, g:i:s a', strtotime((string) $value)) : '',
    'multi' => $value ? ['html' => '<span class="font-semibold text-red-600">Yes</span>'] : 'No',
    default => $value,
};
?>
<h1 class="mb-6 text-2xl font-semibold"><?= e($log['title']) ?> log</h1>

<?= $this->partial('staff-table', [
    'headings' => array_keys($log['columns']),
    'rows' => array_map(fn ($row) => array_map(
        fn ($column) => $format($row[$column[0]] ?? null, $column[1] ?? null),
        array_values($log['columns']),
    ), $rows->items),
    'empty' => 'Nothing logged yet.',
]) ?>

<?= $this->component('pagination', ['paginator' => $rows, 'url' => "/admin/logs/{$key}"]) ?>
