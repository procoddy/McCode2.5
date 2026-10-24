<?php
/**
 * @var list<array{id: int, username: string, roles: string}> $members
 * @var list<array<string, mixed>> $roles
 */
$this->set('title', 'Grant / revoke roles');
$roleOptions = array_column($roles, 'name', 'id');
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Grant / revoke roles</h1>
    <a href="/admin/roles" class="link">&larr; All roles</a>
</div>

<div class="mb-6 grid gap-6 md:grid-cols-2">
    <?php foreach (['grant' => 'Grant a role', 'revoke' => 'Revoke a role'] as $action => $heading): ?>
        <form method="post" action="/admin/roles/<?= $action ?>" class="card space-y-4">
            <?= csrf_field() ?>
            <h2 class="font-semibold"><?= e($heading) ?></h2>
            <?= $this->component('form-field', ['name' => 'username', 'label' => 'Username', 'value' => old('username')]) ?>
            <?= $this->component('form-field', ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => $roleOptions, 'value' => old('role')]) ?>
            <?= $this->component('button', ['label' => ucfirst($action), 'type' => 'submit', 'variant' => $action === 'grant' ? 'primary' : 'danger']) ?>
        </form>
    <?php endforeach; ?>
</div>

<h2 class="mb-3 text-lg font-semibold">Current staff</h2>
<?= $this->partial('staff-table', [
    'headings' => ['Player', 'Roles'],
    'rows' => array_map(fn ($m) => [
        ['html' => '<a class="link" href="/players/' . $m['id'] . '" hx-boost="false">' . e($m['username']) . '</a>'],
        $m['roles'],
    ], $members),
    'empty' => 'Nobody has a staff role yet. The game owner (role "admin") always has full access.',
]) ?>
