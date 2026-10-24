<?php
/**
 * @var array<string, string>|null $system
 * @var list<array<string, mixed>> $recentActions
 * @var string $notepad
 */
$this->set('title', 'Staff Panel');
?>
<h1 class="mb-6 text-2xl font-semibold">Staff Panel</h1>

<?php if ($system !== null): ?>
    <section class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <?php foreach ($system as $label => $value): ?>
            <div class="card">
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($label) ?></p>
                <p class="mt-1 font-semibold break-all"><?= e($value) ?></p>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="mb-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Last 20 staff actions</h2>
            <a href="/admin/logs/staff" class="link text-sm">All staff actions</a>
        </div>
        <?= $this->partial('staff-table', [
            'headings' => ['Time', 'Staff', 'Action', 'IP'],
            'rows' => array_map(fn ($r) => [
                date('M j, g:i a', strtotime($r['created_at'])), $r['username'] ?? '—', $r['action'], $r['ip'] ?? '',
            ], $recentActions),
            'empty' => 'No staff actions yet.',
        ]) ?>
    </section>
<?php endif; ?>

<section>
    <h2 class="mb-3 text-lg font-semibold">Staff notepad</h2>
    <form method="post" action="/admin/notepad" class="card space-y-4">
        <?= csrf_field() ?>
        <?= $this->component('form-field', [
            'name' => 'pad',
            'label' => 'Notes for all staff',
            'type' => 'textarea',
            'value' => $notepad,
        ]) ?>
        <?= $this->component('button', ['label' => 'Save notepad', 'type' => 'submit']) ?>
    </form>
</section>
