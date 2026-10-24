<?php
/**
 * @var string $ip
 * @var bool $invalid
 * @var list<array<string, mixed>> $users
 */
use App\Support\Money;

$this->set('title', 'IP search');
?>
<h1 class="mb-6 text-2xl font-semibold">IP search</h1>

<form method="get" action="/admin/ip-search" class="card mb-6 flex max-w-xl items-end gap-3">
    <div class="flex-1"><?= $this->component('form-field', ['name' => 'ip', 'label' => 'IP address', 'value' => $ip, 'errors' => $invalid ? ['That is not a valid IP address.'] : []]) ?></div>
    <?= $this->component('button', ['label' => 'Search', 'type' => 'submit']) ?>
</form>

<?php if ($ip !== '' && !$invalid): ?>
    <?php if ($users === []): ?>
        <p class="card text-gray-500">No players have used <?= e($ip) ?>.</p>
    <?php else: ?>
        <form method="post" action="/admin/mass-jail" class="space-y-4" hx-confirm="Federally jail every selected player?">
            <?= csrf_field() ?>
            <?= $this->partial('staff-table', [
                'headings' => ['', 'Player', 'Level', 'Money', 'Last login IP', 'Signup IP'],
                'rows' => array_map(fn ($u) => [
                    ['html' => '<input type="checkbox" name="ids[]" value="' . (int) $u['id'] . '" checked class="accent-brand-600" aria-label="Select ' . e($u['username']) . '">'],
                    ['html' => '<a class="link" href="/admin/users/' . (int) $u['id'] . '/edit">' . e($u['username']) . '</a>'],
                    $u['level'],
                    Money::of((int) $u['money'])->format(),
                    $u['last_login_ip'] ?? '',
                    $u['signup_ip'] ?? '',
                ], $users),
                'empty' => '',
            ]) ?>
            <div class="card max-w-xl space-y-3">
                <h2 class="font-semibold">Mass federal jail</h2>
                <?= $this->component('form-field', ['name' => 'days', 'label' => 'Days', 'type' => 'number', 'value' => 300]) ?>
                <?= $this->component('form-field', ['name' => 'reason', 'label' => 'Reason', 'value' => 'Same IP users. Contact staff with your case.']) ?>
                <?= $this->component('button', ['label' => 'Jail selected', 'type' => 'submit', 'variant' => 'danger']) ?>
            </div>
        </form>
    <?php endif; ?>
<?php endif; ?>
