<?php
/** @var list<array<string, mixed>> $active */
$this->set('title', 'Polls');
?>
<h1 class="mb-6 text-2xl font-semibold">Polls</h1>
<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<?php foreach ($active as $poll): ?>
    <section class="card mb-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold"><?= e($poll['question']) ?> <?= $poll['hidden'] ? '<span class="text-xs text-gray-500">(results hidden until closed)</span>' : '' ?></h2>
            <form method="post" action="/admin/polls/<?= (int) $poll['id'] ?>/close" hx-confirm="Close this poll?">
                <?= csrf_field() ?>
                <?= $this->component('button', ['label' => 'Close poll', 'type' => 'submit', 'variant' => 'secondary']) ?>
            </form>
        </div>
        <?= $this->partial('community.poll-results', ['poll' => $poll]) ?>
    </section>
<?php endforeach; ?>

<form method="post" action="/admin/polls" class="card max-w-xl space-y-3">
    <?= csrf_field() ?>
    <h2 class="font-semibold">Start a poll</h2>
    <?= $this->component('form-field', ['name' => 'question', 'label' => 'Question', 'value' => old('question'), 'errors' => []]) ?>
    <?php $old = (array) old('choices', []); ?>
    <?php for ($i = 0; $i < 10; $i++): ?>
        <label class="sr-only" for="choice-<?= $i ?>">Choice <?= $i + 1 ?></label>
        <input id="choice-<?= $i ?>" name="choices[]" class="input" placeholder="Choice <?= $i + 1 ?><?= $i < 2 ? '' : ' (optional)' ?>" value="<?= e($old[$i] ?? '') ?>">
    <?php endfor; ?>
    <?= $this->component('form-field', ['name' => 'hidden', 'label' => 'Hide results until the poll closes', 'type' => 'checkbox']) ?>
    <?= $this->component('button', ['label' => 'Start poll', 'type' => 'submit']) ?>
</form>
