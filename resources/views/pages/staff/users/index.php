<?php
/**
 * @var string $query
 * @var App\Support\Pagination $users
 */
$this->set('title', 'Players');
?>
<h1 class="mb-6 text-2xl font-semibold">Players</h1>

<form method="get" action="/admin/users" class="mb-6 flex max-w-lg gap-3">
    <input type="search" name="q" value="<?= e($query) ?>" placeholder="Username, email or ID" class="input" aria-label="Search players">
    <?= $this->component('button', ['label' => 'Search', 'type' => 'submit', 'variant' => 'secondary']) ?>
</form>

<?= $this->partial('staff-table', [
    'headings' => ['ID', 'Username', 'Email', 'Level', 'Money', 'Account', 'Last seen', ''],
    'rows' => array_map(fn ($u) => [
        $u['id'],
        $u['username'] . ($u['role'] === 'admin' ? ' (owner)' : ''),
        $u['email'],
        $u['level'],
        App\Support\Money::of((int) $u['money'])->format(),
        $u['account_type'],
        $u['last_seen_at'] ? date('M j, g:i a', strtotime($u['last_seen_at'])) : 'Never',
        ['html' => '<a class="link" href="/admin/users/' . (int) $u['id'] . '/edit">Edit</a>'],
    ], $users->items),
    'empty' => 'No players match that search.',
]) ?>

<?= $this->component('pagination', ['paginator' => $users, 'url' => '/admin/users?q=' . rawurlencode($query)]) ?>
