<?php $this->set('title', 'Log in'); ?>
<h1 class="mb-6 text-xl font-semibold">Log in</h1>

<form method="post" action="/login" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?= $this->component('form-field', [
        'name' => 'login',
        'label' => 'Username or email',
        'value' => old('login'),
        'autocomplete' => 'username',
        'errors' => $errors['login'] ?? [],
        'autofocus' => true,
    ]) ?>

    <?= $this->component('form-field', [
        'name' => 'password',
        'label' => 'Password',
        'type' => 'password',
        'autocomplete' => 'current-password',
        'errors' => $errors['password'] ?? [],
    ]) ?>

    <?= $this->component('button', ['label' => 'Log in', 'type' => 'submit', 'block' => true]) ?>
</form>

<p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
    New here? <a href="/register" class="link">Create an account</a>
</p>
