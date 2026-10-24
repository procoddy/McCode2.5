<?php /** @var array<string, mixed> $profile */ $this->set('title', 'Report ' . $profile['username']); ?>
<h1 class="mb-6 text-2xl font-semibold">Report <?= e($profile['username']) ?></h1>

<form method="post" action="/players/<?= (int) $profile['id'] ?>/report" class="card max-w-xl space-y-4">
    <?= csrf_field() ?>
    <?= $this->component('form-field', [
        'name' => 'body',
        'label' => 'What did they do?',
        'type' => 'textarea',
        'value' => old('body'),
        'hint' => 'Only staff can see reports. Include dates and what happened.',
    ]) ?>
    <div class="flex items-center gap-4">
        <?= $this->component('button', ['label' => 'Send report', 'type' => 'submit']) ?>
        <a href="/players/<?= (int) $profile['id'] ?>" class="link">Cancel</a>
    </div>
</form>
