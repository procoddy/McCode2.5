<?php
/**
 * @var array<string, mixed> $player
 * @var array<int, string> $cities
 * @var array<int, string> $houses
 */
$this->set('title', 'Edit ' . $player['username']);
$id = (int) $player['id'];
$minutesLeft = fn (?string $until) => $until && strtotime($until) > time() ? (int) ceil((strtotime($until) - time()) / 60) : 0;
$daysLeft = fn (?string $until) => $until && strtotime($until) > time() ? (int) ceil((strtotime($until) - time()) / 86400) : 0;
$v = fn (string $name, mixed $default = '') => old($name, $player[$name] ?? $default);
$section = fn (string $title) => '<h2 class="col-span-full mt-2 border-b border-gray-200 pb-1 font-semibold dark:border-gray-800">' . e($title) . '</h2>';
$field = fn (string $name, string $label, string $type = 'text', array $extra = []) => $this->component('form-field', [
    'name' => $name, 'label' => $label, 'type' => $type, 'value' => $v($name), ...$extra,
]);
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Edit <?= e($player['username']) ?> <span class="text-gray-400">#<?= $id ?></span></h1>
    <div class="flex gap-3">
        <a href="/players/<?= $id ?>" hx-boost="false" class="btn btn-secondary">View profile</a>
        <a href="/admin/users" class="link self-center">&larr; All players</a>
    </div>
</div>

<form method="post" action="/admin/users/<?= $id ?>" class="card grid gap-4 sm:grid-cols-2 lg:grid-cols-3" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="PUT">

    <?= $section('Account') ?>
    <?= $field('username', 'Username') ?>
    <?= $field('email', 'Email', 'email') ?>
    <?= $field('account_type', 'Account type', 'select', ['options' => array_combine($t = ['Active', 'Demo', 'Staff', 'NPC'], $t)]) ?>
    <?= $field('gender', 'Gender', 'select', ['options' => ['Male' => 'Male', 'Female' => 'Female']]) ?>
    <?= $field('duties', 'Staff duties') ?>
    <?= $this->component('form-field', ['name' => 'donator_days', 'label' => 'Donator days left', 'type' => 'number', 'value' => old('donator_days', $daysLeft($player['donator_until']))]) ?>

    <?= $section('Money') ?>
    <?= $field('money', 'Money', 'number') ?>
    <?= $field('crystals', 'Crystals', 'number') ?>
    <?= $field('bank_balance', 'Bank balance', 'number', ['hint' => 'Leave empty for no account.']) ?>
    <?= $field('cyber_balance', 'Cyber bank balance', 'number', ['hint' => 'Leave empty for no account.']) ?>

    <?= $section('Level and bars') ?>
    <?= $field('level', 'Level', 'number') ?>
    <?= $field('max_energy', 'Max energy', 'number') ?>
    <?= $field('max_brave', 'Max brave', 'number') ?>
    <?= $field('max_will', 'Max will', 'number') ?>
    <?= $field('max_hp', 'Max HP', 'number') ?>

    <?= $section('Stats') ?>
    <?php foreach (['strength' => 'Strength', 'agility' => 'Agility', 'guard' => 'Guard', 'labour' => 'Labour', 'iq' => 'IQ'] as $stat => $label): ?>
        <?= $field($stat, $label, 'number') ?>
    <?php endforeach; ?>

    <?= $section('Location') ?>
    <?= $field('city_id', 'City', 'select', ['options' => $cities]) ?>
    <?= $field('house_id', 'House', 'select', ['options' => $houses]) ?>

    <?= $section('Jail and hospital') ?>
    <?= $this->component('form-field', ['name' => 'jail_minutes', 'label' => 'Jail minutes left', 'type' => 'number', 'value' => old('jail_minutes', $minutesLeft($player['jail_until']))]) ?>
    <?= $field('jail_reason', 'Jail reason') ?>
    <div class="hidden lg:block"></div>
    <?= $this->component('form-field', ['name' => 'hospital_minutes', 'label' => 'Hospital minutes left', 'type' => 'number', 'value' => old('hospital_minutes', $minutesLeft($player['hospital_until']))]) ?>
    <?= $field('hospital_reason', 'Hospital reason') ?>

    <?= $section('Staff notes') ?>
    <div class="col-span-full"><?= $field('staff_notes', 'Notes (only staff can see these)', 'textarea') ?></div>

    <div class="col-span-full pt-2"><?= $this->component('button', ['label' => 'Save player', 'type' => 'submit']) ?></div>
</form>

<div class="mt-6 flex flex-wrap gap-3">
    <?php if (($staffCan ?? fn () => false)('view_user_inventory')): ?>
        <a href="/admin/users/<?= $id ?>/inventory" class="btn btn-secondary">View inventory</a>
    <?php endif; ?>
    <form method="post" action="/admin/users/<?= $id ?>/force-logout" hx-confirm="Log <?= e($player['username']) ?> out?">
        <?= csrf_field() ?>
        <?= $this->component('button', ['label' => 'Force logout', 'type' => 'submit', 'variant' => 'secondary']) ?>
    </form>
    <form method="post" action="/admin/users/<?= $id ?>" hx-confirm="Permanently delete <?= e($player['username']) ?> and everything they own? This cannot be undone.">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <?= $this->component('button', ['label' => 'Delete player', 'type' => 'submit', 'variant' => 'danger']) ?>
    </form>
</div>
