<?php
/** @var list<array<string, mixed>> $players */
$this->set('title', 'Players online');
$ago = function (string $time): string {
    $minutes = (int) floor((time() - strtotime($time)) / 60);

    return $minutes < 1 ? 'just now' : ($minutes === 1 ? '1 minute ago' : "{$minutes} minutes ago");
};
?>
<h1 class="mb-6 text-2xl font-semibold">Players online <span class="text-gray-400">(<?= count($players) ?>)</span></h1>

<?php if ($players === []): ?>
    <div class="card text-center text-gray-500">Nobody else is online right now.</div>
<?php else: ?>
    <ol class="card divide-y divide-gray-200 p-0 dark:divide-gray-800">
        <?php foreach ($players as $i => $p): ?>
            <li class="flex items-center justify-between px-4 py-2.5">
                <span><span class="mr-2 text-gray-400"><?= $i + 1 ?>.</span><a class="link" href="/players/<?= (int) $p['id'] ?>"><?= e($p['username']) ?></a></span>
                <span class="text-sm text-gray-500"><?= e($ago($p['last_seen_at'])) ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
<?php endif; ?>
