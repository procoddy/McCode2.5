<?php $this->set('title', 'Create account'); ?>
<h1 class="mb-6 text-xl font-semibold">Create account</h1>

<form method="post" action="/register" class="space-y-4" novalidate>
    <?= csrf_field() ?>

    <?= $this->component('form-field', [
        'name' => 'username',
        'label' => 'Username',
        'value' => old('username'),
        'autocomplete' => 'username',
        'errors' => $errors['username'] ?? [],
        'attrs' => [
            'hx-post' => '/register/check',
            'hx-trigger' => 'input changed delay:500ms, blur',
            'hx-params' => 'username',
            'hx-target' => '#username-availability',
            'hx-swap' => 'innerHTML',
        ],
        'hint' => '3–20 characters: letters, numbers and underscores.',
        'autofocus' => true,
    ]) ?>
    <p id="username-availability" class="-mt-2 text-sm" aria-live="polite"></p>

    <?= $this->component('form-field', [
        'name' => 'email',
        'label' => 'Email',
        'type' => 'email',
        'value' => old('email'),
        'autocomplete' => 'email',
        'errors' => $errors['email'] ?? [],
        'attrs' => [
            'hx-post' => '/register/check',
            'hx-trigger' => 'input changed delay:500ms, blur',
            'hx-params' => 'email',
            'hx-target' => '#email-availability',
            'hx-swap' => 'innerHTML',
        ],
    ]) ?>
    <p id="email-availability" class="-mt-2 text-sm" aria-live="polite"></p>

    <?= $this->component('form-field', [
        'name' => 'password',
        'label' => 'Password',
        'type' => 'password',
        'autocomplete' => 'new-password',
        'errors' => $errors['password'] ?? [],
        'hint' => 'At least 8 characters.',
    ]) ?>

    <?= $this->component('form-field', [
        'name' => 'password_confirmation',
        'label' => 'Confirm password',
        'type' => 'password',
        'autocomplete' => 'new-password',
    ]) ?>

    <?= $this->component('button', ['label' => 'Create account', 'type' => 'submit', 'block' => true]) ?>
</form>

<p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
    Already playing? <a href="/login" class="link">Log in</a>
</p>
