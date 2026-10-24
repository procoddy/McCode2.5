<?php
/**
 * @var array<string, mixed> $gang
 * @var bool $isLeader
 * @var bool $isPresident
 * @var list<array{body: string, created_at: string}> $events
 * @var App\Modules\Players\Models\Player $player
 */
use App\Support\Money;

$this->set('title', $gang['name']);
?>
<?= $this->partial('gang.nav', ['gang' => $gang, 'isLeader' => $isLeader, 'active' => '/gang']) ?>
<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<?php if ($gang['announcement']): ?>
    <div class="alert alert-info mb-6 whitespace-pre-line" role="note"><?= e($gang['announcement']) ?></div>
<?php endif; ?>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <h2 class="mb-3 font-semibold">Last 10 gang events</h2>
        <?php if ($events === []): ?>
            <p class="text-sm text-gray-500">Nothing has happened yet.</p>
        <?php else: ?>
            <ul class="space-y-2 text-sm">
                <?php foreach ($events as $event): ?>
                    <li><time class="text-gray-500" datetime="<?= e($event['created_at']) ?>"><?= e(date('M j, g:i a', strtotime($event['created_at']))) ?></time> — <?= e($event['body']) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <div class="space-y-6">
        <section class="card">
            <h2 class="mb-3 font-semibold">Summary</h2>
            <dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1.5 text-sm">
                <dt class="text-gray-500">President</dt><dd><?= e($gang['president_username'] ?? 'N/A') ?></dd>
                <dt class="text-gray-500">Vice-president</dt><dd><?= e($gang['vice_president_username'] ?? 'N/A') ?></dd>
                <dt class="text-gray-500">Members</dt><dd><?= (int) $gang['member_count'] ?>/<?= (int) $gang['capacity'] ?></dd>
                <dt class="text-gray-500">Respect</dt><dd><?= number_format((int) $gang['respect']) ?></dd>
                <dt class="text-gray-500">Vault money</dt><dd><?= e(Money::of((int) $gang['money'])->format()) ?></dd>
                <dt class="text-gray-500">Vault crystals</dt><dd><?= number_format((int) $gang['crystals']) ?></dd>
            </dl>
        </section>

        <form method="post" action="/gang/donate" class="card space-y-3">
            <?= csrf_field() ?>
            <h2 class="font-semibold">Donate to the vault</h2>
            <p class="text-sm text-gray-500">You have <?= e($player->money->format()) ?> and <?= number_format($player->crystals) ?> crystals.</p>
            <?= $this->component('form-field', ['name' => 'money', 'label' => 'Money', 'type' => 'number', 'value' => old('money', 0), 'errors' => []]) ?>
            <?= $this->component('form-field', ['name' => 'crystals', 'label' => 'Crystals', 'type' => 'number', 'value' => old('crystals', 0), 'errors' => []]) ?>
            <?= $this->component('button', ['label' => 'Donate', 'type' => 'submit']) ?>
        </form>

        <?php if (!$isLeader): ?>
            <form method="post" action="/gang/leave" hx-confirm="Leave <?= e($gang['name']) ?>?">
                <?= csrf_field() ?>
                <?= $this->component('button', ['label' => 'Leave gang', 'type' => 'submit', 'variant' => 'danger']) ?>
            </form>
        <?php endif; ?>
    </div>
</div>
