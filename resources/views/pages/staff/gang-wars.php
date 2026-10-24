<?php
/** @var list<array<string, mixed>> $wars */
$this->set('title', 'Gang wars');
?>
<h1 class="mb-6 text-2xl font-semibold">Gang wars</h1>

<?php if ($wars === []): ?>
    <div class="card text-gray-500">No gangs are at war.</div>
<?php else: ?>
    <ul class="space-y-2">
        <?php foreach ($wars as $war): ?>
            <li class="card flex flex-wrap items-center justify-between gap-3">
                <span>
                    <a class="link" href="/admin/data/gangs/<?= (int) $war['declarer_id'] ?>/edit"><?= e($war['declarer']) ?></a>
                    vs
                    <a class="link" href="/admin/data/gangs/<?= (int) $war['declared_id'] ?>/edit"><?= e($war['declared']) ?></a>
                    <span class="text-sm text-gray-500">since <?= e(date('M j, Y', strtotime($war['created_at']))) ?></span>
                </span>
                <form method="post" action="/admin/gang-wars/<?= (int) $war['id'] ?>" hx-confirm="End this war?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <?= $this->component('button', ['label' => 'End war', 'type' => 'submit', 'variant' => 'danger']) ?>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
