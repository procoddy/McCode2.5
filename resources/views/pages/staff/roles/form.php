<?php
/** @var array<string, mixed>|null $role */
$editing = $role !== null;
$this->set('title', $editing ? 'Edit role' : 'New role');
$hasOld = app(App\Core\Session::class)->has('_old_input');
$checked = fn (string $p) => $hasOld ? in_array($p, (array) old('permissions', []), true) : !empty($role[$p]);
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= $editing ? 'Edit ' . e($role['name']) : 'New staff role' ?></h1>
    <a href="/admin/roles" class="link">&larr; All roles</a>
</div>

<form method="post" action="<?= $editing ? '/admin/roles/' . (int) $role['id'] : '/admin/roles' ?>" class="card max-w-3xl space-y-4">
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="_method" value="PUT"><?php endif; ?>
    <?= $this->component('form-field', ['name' => 'name', 'label' => 'Name', 'value' => old('name', $role['name'] ?? '')]) ?>

    <fieldset>
        <legend class="mb-2 text-sm font-medium">Permissions</legend>
        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">Administrator grants everything else.</p>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach (App\Modules\Staff\Services\StaffPermissions::ALL as $permission): ?>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" class="size-4 accent-brand-600" name="permissions[]" value="<?= e($permission) ?>"<?= $checked($permission) ? ' checked' : '' ?>>
                    <?= e(ucfirst(str_replace('_', ' ', $permission))) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <?= $this->component('button', ['label' => $editing ? 'Save role' : 'Create role', 'type' => 'submit']) ?>
</form>

<?php if ($editing): ?>
    <form method="post" action="/admin/roles/<?= (int) $role['id'] ?>" class="mt-6"
          hx-confirm="Delete the <?= e($role['name']) ?> role? Everyone who has it loses it.">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="DELETE">
        <?= $this->component('button', ['label' => 'Delete role', 'type' => 'submit', 'variant' => 'danger']) ?>
    </form>
<?php endif; ?>
