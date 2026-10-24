<?php
/**
 * @var list<array<string, mixed>> $staff
 * @var string $onlineSince
 */
$this->set('title', 'Staff');
?>
<h1 class="mb-6 text-2xl font-semibold">Staff</h1>

<?= $this->partial('staff-table', [
    'headings' => ['Player', 'Roles', 'Level', 'Last seen', 'Status'],
    'rows' => array_map(fn ($s) => [
        ['html' => '<a class="link" href="/players/' . (int) $s['id'] . '">' . e($s['username']) . '</a>'],
        $s['roles'],
        $s['level'],
        $s['last_seen_at'] ? date('M j, g:i a', strtotime($s['last_seen_at'])) : 'Never',
        ['html' => $this->partial('players.online-dot', ['online' => $s['last_seen_at'] > $onlineSince])],
    ], $staff),
    'empty' => 'No staff yet.',
]) ?>
