<?php
/** @var list<array<string, mixed>> $roles */
$this->set('title', 'Staff roles');
$describe = function (array $role): string {
    if (!empty($role['administrator'])) {
        return 'Everything (administrator)';
    }
    $granted = array_filter(App\Modules\Staff\Services\StaffPermissions::ALL, fn ($p) => !empty($role[$p]));

    return $granted === [] ? 'None' : implode(', ', array_map(fn ($p) => ucfirst(str_replace('_', ' ', $p)), $granted));
};
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Staff roles</h1>
    <div class="flex gap-3">
        <a href="/admin/roles/members" class="btn btn-secondary">Grant / revoke</a>
        <a href="/admin/roles/create" class="btn btn-primary">New role</a>
    </div>
</div>

<?= $this->partial('staff-table', [
    'headings' => ['Role', 'Permissions', ''],
    'rows' => array_map(fn ($role) => [
        $role['name'],
        $describe($role),
        ['html' => '<a class="link" href="/admin/roles/' . (int) $role['id'] . '/edit">Edit</a>'],
    ], $roles),
    'empty' => 'No roles yet.',
]) ?>
