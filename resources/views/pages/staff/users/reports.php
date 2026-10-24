<?php
/** @var list<array<string, mixed>> $reports */
$this->set('title', 'Player reports');
$profile = fn (?int $id, ?string $name) => $id
    ? ['html' => '<a class="link" hx-boost="false" href="/players/' . $id . '">' . e($name ?? "#{$id}") . '</a>']
    : '—';
?>
<h1 class="mb-6 text-2xl font-semibold">Player reports</h1>

<?= $this->partial('staff-table', [
    'headings' => ['When', 'Reporter', 'Reported', 'What they did', ''],
    'rows' => array_map(fn ($r) => [
        date('M j, g:i a', strtotime($r['created_at'])),
        $profile($r['reporter_id'] === null ? null : (int) $r['reporter_id'], $r['reporter']),
        $profile($r['reported_id'] === null ? null : (int) $r['reported_id'], $r['reported']),
        $r['body'],
        ['html' => '<form method="post" action="/admin/reports/' . (int) $r['id'] . '" hx-confirm="Clear this report?">'
            . csrf_field() . '<input type="hidden" name="_method" value="DELETE">'
            . '<button class="link cursor-pointer" type="submit">Clear</button></form>'],
    ], $reports),
    'empty' => 'No open reports.',
]) ?>
