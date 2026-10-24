<?php
/**
 * @var App\Modules\Players\Models\Player $player
 * @var int $maxBet
 * @var array{reels: list<int>, bet: int, won: int, matched: int}|null $result
 * @var int|string|null $bet
 * @var bool $oob
 */
use App\Support\Money;

$result ??= null;
?>
<section id="slots-panel" class="max-w-xl space-y-6">
    <?php if (!empty($errors['bet'])): ?>
        <?= $this->component('alert', ['message' => $errors['bet'][0], 'type' => 'error']) ?>
    <?php endif; ?>

    <?php if ($result !== null): ?>
        <div class="card space-y-3 text-center" role="status">
            <div class="flex justify-center gap-3" aria-label="Reels: <?= implode(' ', $result['reels']) ?>">
                <?php foreach ($result['reels'] as $digit): ?>
                    <span class="flex size-16 items-center justify-center rounded-lg border-2 border-gray-300 bg-white font-mono text-3xl font-bold dark:border-gray-700 dark:bg-gray-950"><?= $digit ?></span>
                <?php endforeach; ?>
            </div>
            <p>
                You bet <?= e(Money::of($result['bet'])->format()) ?>
                <?php if ($result['won'] > 0): ?>
                    and won <strong><?= e(Money::of($result['won'])->format()) ?></strong> by lining up <?= $result['matched'] ?> numbers,
                    pocketing you <?= e(Money::of($result['won'] - $result['bet'])->format()) ?> extra.
                <?php else: ?>
                    and lost it.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <p>Ready to try your luck? Line up two numbers for 3&times; your bet, or all three for 26&times;.</p>
    <?php endif; ?>

    <form method="post" action="/slots" hx-post="/slots" hx-target="#slots-panel" hx-swap="outerHTML" class="card space-y-4">
        <?= csrf_field() ?>
        <?= $this->component('form-field', [
            'name' => 'bet', 'label' => 'Bet', 'type' => 'number', 'value' => $bet ?? old('bet', 5), 'errors' => [],
            'hint' => 'The maximum bet for your level is ' . Money::of($maxBet)->format() . '. You have ' . $player->money->format() . '.',
            'attrs' => ['min' => '1', 'max' => (string) $maxBet],
        ]) ?>
        <?= $this->component('button', ['label' => $result !== null ? 'Spin again' : 'Play!', 'type' => 'submit']) ?>
    </form>
</section>
<?php if (!empty($oob)): ?>
    <?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
<?php endif; ?>
