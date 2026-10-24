<?php
/** @var array<string, array{value: string, type: string}> $settings */
$this->set('title', 'Basic settings');
$labels = [
    'validate_on' => 'Gym/crime validation',
    'validate_period' => 'Validation period',
    'regcap_on' => 'Registration captcha',
    'sendcrys_on' => 'Players can send crystals',
    'sendbank_on' => 'Players can make bank transfers',
    'willp_item' => 'Will potion item ID',
];
?>
<h1 class="mb-2 text-2xl font-semibold">Basic settings</h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
    Game balance numbers (bank fees, crystal temple prices, regeneration rates) live in <code>config/game.php</code>.
</p>

<form method="post" action="/admin/settings" class="card max-w-xl space-y-4">
    <?= csrf_field() ?>
    <?php foreach ($settings as $name => $setting): ?>
        <?php if ($name === 'staff_pad') continue; ?>
        <?= $this->component('form-field', [
            'name' => $name,
            'label' => $labels[$name] ?? ucfirst(str_replace('_', ' ', $name)),
            'type' => match ($setting['type']) { 'bool' => 'checkbox', 'int' => 'number', default => 'text' },
            'value' => $setting['value'],
            'checked' => $setting['value'] === '1',
        ]) ?>
    <?php endforeach; ?>
    <?= $this->component('button', ['label' => 'Save settings', 'type' => 'submit']) ?>
</form>
