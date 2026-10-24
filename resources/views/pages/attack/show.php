<?php $this->set('title', "Attack {$target->username}"); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Fight: <?= e($me->username) ?> vs <?= e($target->username) ?></h1>
    <a href="/players/<?= $target->id ?>" class="link">&larr; Profile</a>
</div>

<?= $this->partial('attack.panel', compact('me', 'target', 'blockedReason', 'awaitingChoice', 'log')) ?>
