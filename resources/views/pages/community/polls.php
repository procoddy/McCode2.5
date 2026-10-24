<?php
/**
 * @var list<array<string, mixed>> $active
 * @var list<array<string, mixed>> $closed
 * @var list<int> $voted
 */
$this->set('title', 'Polls');
?>
<h1 class="mb-6 text-2xl font-semibold">Polls</h1>
<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<?php if ($active === []): ?>
    <div class="card mb-6 text-center text-gray-500 dark:text-gray-400">There are no open polls right now.</div>
<?php endif; ?>

<div class="mb-8 space-y-6">
    <?php foreach ($active as $poll): ?>
        <section class="card space-y-4">
            <h2 class="font-semibold"><?= e($poll['question']) ?></h2>
            <?php if (in_array((int) $poll['id'], $voted, true)): ?>
                <?php if ($poll['hidden']): ?>
                    <p class="text-sm text-gray-500">You voted. Results will be shown when the poll closes.</p>
                <?php else: ?>
                    <?= $this->partial('community.poll-results', ['poll' => $poll]) ?>
                <?php endif; ?>
            <?php else: ?>
                <form method="post" action="/polls/<?= (int) $poll['id'] ?>/vote" class="space-y-2">
                    <?= csrf_field() ?>
                    <?php foreach ($poll['choices'] as $i => $choice): ?>
                        <label class="flex items-center gap-2"><input type="radio" name="choice" value="<?= $i ?>" class="accent-brand-600" required> <?= e($choice) ?></label>
                    <?php endforeach; ?>
                    <?= $this->component('button', ['label' => 'Vote', 'type' => 'submit']) ?>
                </form>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<?php if ($closed !== []): ?>
    <h2 class="mb-3 text-lg font-semibold">Closed polls</h2>
    <div class="space-y-4">
        <?php foreach ($closed as $poll): ?>
            <section class="card space-y-3">
                <h3 class="font-semibold"><?= e($poll['question']) ?></h3>
                <?= $this->partial('community.poll-results', ['poll' => $poll]) ?>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
