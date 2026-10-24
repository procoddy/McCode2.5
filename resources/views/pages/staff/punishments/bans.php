<?php
/**
 * @var list<array<string, mixed>> $mail
 * @var list<array<string, mixed>> $forum
 */
$this->set('title', 'Mail & forum bans');
?>
<h1 class="mb-6 text-2xl font-semibold">Mail &amp; forum bans</h1>

<div class="grid gap-6 lg:grid-cols-2">
    <?php foreach (['mail' => ['Mail ban', $mail], 'forum' => ['Forum ban', $forum]] as $type => [$title, $banned]): ?>
        <section class="space-y-4">
            <form method="post" action="/admin/bans/<?= $type ?>" class="card space-y-3">
                <?= csrf_field() ?>
                <h2 class="font-semibold"><?= e($title) ?></h2>
                <?= $this->component('form-field', ['name' => 'username', 'label' => 'Username', 'errors' => []]) ?>
                <?= $this->component('form-field', ['name' => 'days', 'label' => 'Days', 'type' => 'number', 'value' => 7, 'errors' => []]) ?>
                <?= $this->component('form-field', ['name' => 'reason', 'label' => 'Reason (shown to the player)', 'errors' => []]) ?>
                <?= $this->component('button', ['label' => 'Ban', 'type' => 'submit', 'variant' => 'danger']) ?>
            </form>
            <?= $this->partial('staff-table', [
                'headings' => ['Player', 'Until', 'Reason', ''],
                'rows' => array_map(fn ($b) => [
                    $b['username'],
                    date('M j Y, g:i a', strtotime($b['until'])),
                    $b['reason'] ?? '',
                    ['html' => '<form method="post" action="/admin/bans/' . $type . '/' . (int) $b['id'] . '/remove">' . csrf_field()
                        . '<button class="link cursor-pointer" type="submit">Unban</button></form>'],
                ], $banned),
                'empty' => 'Nobody is banned.',
            ]) ?>
        </section>
    <?php endforeach; ?>
</div>
<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>
