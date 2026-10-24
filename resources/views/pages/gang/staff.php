<?php
/**
 * Legacy yourgang.php?action=staff: every leader tool on one page.
 *
 * @var array<string, mixed> $gang
 * @var bool $isLeader
 * @var bool $isPresident
 * @var list<array<string, mixed>> $members
 * @var list<array<string, mixed>> $applications
 * @var list<array<string, mixed>> $crimes
 * @var list<array<string, mixed>> $wars
 * @var list<array<string, mixed>> $surrenders
 * @var list<array<string, mixed>> $otherGangs
 */
use App\Support\Money;

$this->set('title', 'Gang staff room');
$memberOptions = array_column($members, 'username', 'id');
$form = function (string $action, string $title, string $fields, string $button, string $confirm = '') {
    return '<form method="post" action="/gang/staff/' . $action . '" class="card space-y-3"' . ($confirm !== '' ? ' hx-confirm="' . e($confirm) . '"' : '') . '>'
        . csrf_field() . '<h3 class="font-semibold">' . e($title) . '</h3>' . $fields
        . '<button type="submit" class="btn btn-primary">' . e($button) . '</button></form>';
};
$field = fn (array $props) => $this->component('form-field', $props + ['errors' => []]);
?>
<?= $this->partial('gang.nav', ['gang' => $gang, 'isLeader' => $isLeader, 'active' => '/gang/staff']) ?>
<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<section class="mb-8">
    <h2 class="mb-3 text-lg font-semibold">Applications</h2>
    <?php if ($applications === []): ?>
        <p class="card text-sm text-gray-500">No pending applications.</p>
    <?php else: ?>
        <ul class="space-y-2">
            <?php foreach ($applications as $app): ?>
                <li class="card flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="/players/<?= (int) $app['user_id'] ?>" class="link font-medium"><?= e($app['username']) ?></a>
                        <span class="text-sm text-gray-500">Level <?= (int) $app['level'] ?></span>
                        <?php if ($app['body']): ?><p class="mt-1 text-sm whitespace-pre-line"><?= e($app['body']) ?></p><?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                        <?php foreach (['accept' => 'Accept', 'decline' => 'Decline'] as $decision => $label): ?>
                            <form method="post" action="/gang/staff/applications/<?= (int) $app['id'] ?>/<?= $decision ?>">
                                <?= csrf_field() ?>
                                <?= $this->component('button', ['label' => $label, 'type' => 'submit', 'variant' => $decision === 'accept' ? 'primary' : 'secondary']) ?>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<h2 class="mb-3 text-lg font-semibold">General</h2>
<div class="mb-8 grid gap-6 lg:grid-cols-2">
    <?= $form('vault', 'Vault (' . Money::of((int) $gang['money'])->format() . ', ' . number_format((int) $gang['crystals']) . ' crystals)',
        $field(['name' => 'user_id', 'label' => 'Give to', 'type' => 'select', 'options' => $memberOptions])
        . $field(['name' => 'money', 'label' => 'Money', 'type' => 'number', 'value' => 0])
        . $field(['name' => 'crystals', 'label' => 'Crystals', 'type' => 'number', 'value' => 0]),
        'Give from vault') ?>

    <?= $form('mass-payment', 'Mass payment',
        $field(['name' => 'amount', 'label' => 'Amount for each member', 'type' => 'number', 'hint' => count($members) . ' members'])
        , 'Pay everyone', 'Pay every member from the vault?') ?>

    <?= $form('vice-president', 'Change vice-president',
        $field(['name' => 'user_id', 'label' => 'New vice-president', 'type' => 'select', 'options' => $memberOptions, 'value' => $gang['vice_president_id']]),
        'Change') ?>

    <?= $form('upgrade', 'Upgrade capacity (' . (int) $gang['member_count'] . '/' . (int) $gang['capacity'] . ')',
        $field(['name' => 'slots', 'label' => 'Extra member slots', 'type' => 'number', 'value' => 1, 'hint' => Money::of((int) config('game.gangs.capacity_slot_cost'))->format() . ' each, paid from the vault']),
        'Upgrade') ?>

    <?php if ($gang['organised_crime_id']): ?>
        <div class="card"><h3 class="font-semibold">Organised crime</h3><p class="mt-2 text-sm">Planning <?= e($gang['organised_crime_name']) ?> until <?= e(date('M j, g:i a', strtotime($gang['crime_ready_at']))) ?>.</p></div>
    <?php elseif ($crimes === []): ?>
        <div class="card"><h3 class="font-semibold">Organised crime</h3><p class="mt-2 text-sm text-gray-500">Your gang doesn't have enough members for any crime yet.</p></div>
    <?php else: ?>
        <?= $form('crime', 'Organised crime',
            $field(['name' => 'crime_id', 'label' => 'Crime', 'type' => 'select', 'options' => array_combine(
                array_column($crimes, 'id'),
                array_map(fn ($c) => "{$c['name']} ({$c['members_required']} members)", $crimes),
            )]),
            'Start planning') ?>
    <?php endif; ?>

    <?= $form('announcement', 'Gang announcement',
        $field(['name' => 'announcement', 'label' => 'Announcement', 'type' => 'textarea', 'value' => $gang['announcement'] ?? '']),
        'Save announcement') ?>
</div>

<?php if ($isPresident): ?>
    <h2 class="mb-3 text-lg font-semibold">President only</h2>
    <div class="grid gap-6 lg:grid-cols-2">
        <?= $form('details', 'Name, tag and description',
            $field(['name' => 'name', 'label' => 'Name', 'value' => $gang['name']])
            . $field(['name' => 'prefix', 'label' => 'Tag', 'value' => $gang['prefix'], 'attrs' => ['maxlength' => '12']])
            . $field(['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'value' => $gang['description'] ?? '']),
            'Save details') ?>

        <?= $form('president', 'Hand over the presidency',
            $field(['name' => 'user_id', 'label' => 'New president', 'type' => 'select', 'options' => $memberOptions, 'value' => $gang['president_id']]),
            'Make president', 'You will no longer be president. Continue?') ?>

        <?= $form('mass-mail', 'Mail every member',
            $field(['name' => 'subject', 'label' => 'Subject'])
            . $field(['name' => 'body', 'label' => 'Message', 'type' => 'textarea']),
            'Send') ?>

        <?php if ($otherGangs !== []): ?>
            <?= $form('war', 'Declare war',
                $field(['name' => 'gang_id', 'label' => 'Gang', 'type' => 'select', 'options' => array_column($otherGangs, 'name', 'id')]),
                'Declare war', 'Declare war on this gang?') ?>
        <?php endif; ?>

        <?php if ($wars !== []): ?>
            <?= $form('surrender', 'Surrender',
                $field(['name' => 'war_id', 'label' => 'War against', 'type' => 'select', 'options' => array_column($wars, 'enemy_name', 'id')])
                . $field(['name' => 'message', 'label' => 'Message']),
                'Offer surrender') ?>
        <?php endif; ?>

        <div class="card space-y-3">
            <h3 class="font-semibold">Surrenders offered to you</h3>
            <?php if ($surrenders === []): ?>
                <p class="text-sm text-gray-500">None.</p>
            <?php endif; ?>
            <?php foreach ($surrenders as $s): ?>
                <form method="post" action="/gang/staff/surrenders/<?= (int) $s['id'] ?>/accept" class="flex flex-wrap items-center justify-between gap-2">
                    <?= csrf_field() ?>
                    <span class="text-sm"><strong><?= e($s['from_gang_name']) ?></strong><?= $s['message'] !== '' ? ': “' . e($s['message']) . '”' : '' ?></span>
                    <?= $this->component('button', ['label' => 'Accept', 'type' => 'submit', 'variant' => 'secondary']) ?>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
