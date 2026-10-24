<?php
/**
 * @var array<string, mixed> $gang
 * @var list<array<string, mixed>> $members
 * @var App\Modules\Players\Models\Player $player
 */
$this->set('title', $gang['name']);
?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($gang['name']) ?> <?php if ($gang['prefix'] !== ''): ?><span class="text-gray-500">[<?= e($gang['prefix']) ?>]</span><?php endif; ?></h1>
    <a href="/gangs" class="link">&larr; All gangs</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="card space-y-3 lg:col-span-2">
        <?php if ($gang['description']): ?>
            <p class="whitespace-pre-line"><?= e($gang['description']) ?></p>
        <?php endif; ?>
        <dl class="grid grid-cols-[max-content_1fr] gap-x-6 gap-y-1.5 text-sm">
            <dt class="text-gray-500">President</dt><dd><?= e($gang['president_username'] ?? '—') ?></dd>
            <dt class="text-gray-500">Vice-president</dt><dd><?= e($gang['vice_president_username'] ?? '—') ?></dd>
            <dt class="text-gray-500">Members</dt><dd><?= (int) $gang['member_count'] ?>/<?= (int) $gang['capacity'] ?></dd>
            <dt class="text-gray-500">Respect</dt><dd><?= number_format((int) $gang['respect']) ?></dd>
        </dl>
    </section>

    <?php if ($player->gangId === null): ?>
        <form method="post" action="/gangs/<?= (int) $gang['id'] ?>/apply" class="card space-y-4">
            <?= csrf_field() ?>
            <h2 class="font-semibold">Apply to join</h2>
            <?= $this->component('form-field', ['name' => 'body', 'label' => 'Why should they let you in?', 'type' => 'textarea', 'value' => old('body')]) ?>
            <?= $this->component('button', ['label' => 'Send application', 'type' => 'submit']) ?>
        </form>
    <?php endif; ?>
</div>

<h2 class="mt-8 mb-3 font-semibold">Members</h2>
<ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($members as $m): ?>
        <li class="card py-2"><a href="/players/<?= (int) $m['id'] ?>" class="link"><?= e($m['username']) ?></a> <span class="text-sm text-gray-500">Level <?= (int) $m['level'] ?></span></li>
    <?php endforeach; ?>
</ul>
