<?php
/**
 * Swapped into #crime-result, plus the top bar out of band.
 *
 * @var App\Modules\Crimes\Models\CrimeOutcome|null $outcome
 * @var string|null $error
 * @var App\Modules\Players\Models\Player $player
 */
$styles = [
    'success' => 'alert-success',
    'failure' => 'alert-warning',
    'jailed' => 'alert-error',
];
?>
<?php if (!empty($error)): ?>
    <?= $this->component('alert', ['message' => $error, 'type' => 'error']) ?>
<?php elseif (isset($outcome)): ?>
    <div class="alert <?= $styles[$outcome->result] ?> space-y-2" role="status">
        <p class="font-semibold"><?= e($outcome->crime->name) ?></p>
        <?php /* Staff-written text is escaped; legacy printed it as raw HTML. */ ?>
        <?php if ($outcome->crime->initialText !== ''): ?>
            <p><?= nl2br(e($outcome->crime->initialText)) ?></p>
        <?php endif; ?>
        <p><?= nl2br(e($outcome->text)) ?></p>
        <?php if ($outcome->result !== 'jailed'): ?>
            <form method="post" action="/crimes/<?= $outcome->crime->id ?>"
                  hx-post="/crimes/<?= $outcome->crime->id ?>" hx-target="#crime-result" hx-swap="innerHTML">
                <?= csrf_field() ?>
                <button type="submit" class="link cursor-pointer">Try again</button>
            </form>
        <?php else: ?>
            <p><a href="/crimes" class="link">Back to crimes</a></p>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
