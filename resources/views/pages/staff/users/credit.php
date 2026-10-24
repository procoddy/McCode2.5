<?php
/** @var bool $mass */
$this->set('title', $mass ? 'Mass payment' : 'Credit player');
?>
<h1 class="mb-2 text-2xl font-semibold"><?= $mass ? 'Mass payment' : 'Credit player' ?></h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
    <?= $mass ? 'Give every player money and/or crystals.' : 'Give one player money and/or crystals. They get an event telling them.' ?>
</p>

<form method="post" action="<?= $mass ? '/admin/users/mass-credit' : '/admin/users/credit' ?>" class="card max-w-md space-y-4"
      <?= $mass ? 'hx-confirm="Credit every player in the game?"' : '' ?>>
    <?= csrf_field() ?>
    <?php if (!$mass): ?>
        <?= $this->component('form-field', ['name' => 'username', 'label' => 'Username', 'value' => old('username')]) ?>
    <?php endif; ?>
    <?= $this->component('form-field', ['name' => 'money', 'label' => 'Money', 'type' => 'number', 'value' => old('money', 0)]) ?>
    <?= $this->component('form-field', ['name' => 'crystals', 'label' => 'Crystals', 'type' => 'number', 'value' => old('crystals', 0)]) ?>
    <?= $this->component('button', ['label' => $mass ? 'Pay everyone' : 'Credit', 'type' => 'submit']) ?>
</form>
