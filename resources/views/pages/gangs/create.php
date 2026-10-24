<?php
/** @var int $cost */
$this->set('title', 'Create a gang');
?>
<h1 class="mb-2 text-2xl font-semibold">Create a gang</h1>
<p class="mb-6 text-gray-600 dark:text-gray-400">Starting a gang costs <?= e(App\Support\Money::of($cost)->format()) ?>. You'll be its president.</p>

<form method="post" action="/gangs/create" class="card max-w-xl space-y-4">
    <?= csrf_field() ?>
    <?= $this->component('form-field', ['name' => 'name', 'label' => 'Name', 'value' => old('name'), 'attrs' => ['maxlength' => '255']]) ?>
    <?= $this->component('form-field', ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'value' => old('description')]) ?>
    <?= $this->component('button', ['label' => 'Create gang for ' . App\Support\Money::of($cost)->format(), 'type' => 'submit']) ?>
</form>
