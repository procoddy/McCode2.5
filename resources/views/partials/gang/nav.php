<?php
/**
 * @var array $gang
 * @var bool $isLeader
 * @var string $active
 */
$links = ['/gang' => 'Home', '/gang/members' => 'Members', '/gang/wars' => 'Wars', '/gang/attack-logs' => 'Attack logs', '/gang/crimes' => 'Crimes', '/gang/forum' => 'Forum'];
if ($isLeader) {
    $links['/gang/staff'] = 'Staff room';
}
?>
<div class="mb-6">
    <h1 class="text-2xl font-semibold"><?= e($gang['prefix'] !== '' ? "[{$gang['prefix']}] " : '') ?><?= e($gang['name']) ?></h1>
    <nav class="mt-3 flex flex-wrap gap-1" aria-label="Gang">
        <?php foreach ($links as $href => $label): ?>
            <a href="<?= $href ?>" class="rounded-md px-3 py-1.5 text-sm font-medium <?= $active === $href ? 'bg-gray-200 dark:bg-gray-800' : 'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</div>
