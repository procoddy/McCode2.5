<?php
/** @var list<array<string, mixed>> $jailed */
$this->set('title', 'Federal jail');
?>
<h1 class="mb-6 text-2xl font-semibold">Federal jail</h1>

<form method="post" action="/admin/fedjail" class="card mb-6 max-w-xl space-y-4">
    <?= csrf_field() ?>
    <p class="text-sm text-gray-600 dark:text-gray-400">A jailed player is logged out and can't play until released or the time runs out.</p>
    <?= $this->component('form-field', ['name' => 'username', 'label' => 'Username', 'value' => old('username')]) ?>
    <?= $this->component('form-field', ['name' => 'days', 'label' => 'Days', 'type' => 'number', 'value' => old('days', 7)]) ?>
    <?= $this->component('form-field', ['name' => 'reason', 'label' => 'Reason (shown to the player)', 'type' => 'textarea', 'value' => old('reason')]) ?>
    <?= $this->component('button', ['label' => 'Jail player', 'type' => 'submit', 'variant' => 'danger']) ?>
</form>

<h2 class="mb-3 text-lg font-semibold">Currently jailed</h2>
<?= $this->partial('staff-table', [
    'headings' => ['Player', 'Until', 'Reason', 'Jailed by', ''],
    'rows' => array_map(fn ($j) => [
        $j['username'],
        date('M j Y, g:i a', strtotime($j['jailed_until'])),
        $j['reason'],
        $j['jailed_by'] ?? '—',
        ['html' => '<form method="post" action="/admin/fedjail/' . (int) $j['user_id'] . '/release">'
            . csrf_field() . '<button class="link cursor-pointer" type="submit">Release</button></form>'],
    ], $jailed),
    'empty' => 'Nobody is in federal jail.',
]) ?>
