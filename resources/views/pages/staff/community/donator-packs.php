<?php
/** @var array<string, array{name: string, money: int, crystals: int, iq: int, days: int}> $packs */
use App\Support\Money;

$this->set('title', 'Give donator pack');
?>
<h1 class="mb-2 text-2xl font-semibold">Give donator pack</h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400">The player receives the pack's rewards and donator days. Packs are defined in <code>config/game.php</code>.</p>
<form method="post" action="/admin/donator-packs" class="card max-w-xl space-y-4">
    <?= csrf_field() ?>
    <?= $this->component('form-field', ['name' => 'username', 'label' => 'Username', 'value' => old('username')]) ?>
    <fieldset class="space-y-2">
        <legend class="mb-1 text-sm font-medium">Pack</legend>
        <?php foreach ($packs as $key => $pack): ?>
            <label class="flex items-start gap-2 text-sm">
                <input type="radio" name="pack" value="<?= e($key) ?>" class="mt-1 accent-brand-600" <?= old('pack') === $key ? 'checked' : '' ?> required>
                <span><strong><?= e($pack['name']) ?></strong> — <?= e(implode(', ', array_filter([
                    $pack['money'] ? Money::of($pack['money'])->format() : null,
                    $pack['crystals'] ? "{$pack['crystals']} crystals" : null,
                    $pack['iq'] ? "{$pack['iq']} IQ" : null,
                    "{$pack['days']} donator days",
                ]))) ?></span>
            </label>
        <?php endforeach; ?>
        <?php if (!empty($errors['pack'])): ?><p class="text-sm text-red-600"><?= e($errors['pack'][0]) ?></p><?php endif; ?>
    </fieldset>
    <?= $this->component('button', ['label' => 'Give pack', 'type' => 'submit']) ?>
</form>
