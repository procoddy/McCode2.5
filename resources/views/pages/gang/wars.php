<?php
/**
 * @var array<string, mixed> $gang
 * @var bool $isLeader
 * @var list<array<string, mixed>> $wars
 */
$this->set('title', 'Gang wars');
?>
<?= $this->partial('gang.nav', ['gang' => $gang, 'isLeader' => $isLeader, 'active' => '/gang/wars']) ?>

<?php if ($wars === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">Your gang is not at war.</div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($wars as $war): ?>
            <li class="card flex flex-wrap items-center justify-between gap-2">
                <span>
                    <?= (int) $war['declarer_gang_id'] === (int) $gang['id'] ? 'You declared war on' : 'War declared on you by' ?>
                    <a href="/gangs/<?= (int) $war['enemy_id'] ?>" class="link"><?= e($war['enemy_name']) ?></a>
                    <span class="text-sm text-gray-500">(<?= number_format((int) $war['enemy_respect']) ?> respect)</span>
                </span>
                <time class="text-sm text-gray-500"><?= e(date('M j, Y', strtotime($war['created_at']))) ?></time>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
