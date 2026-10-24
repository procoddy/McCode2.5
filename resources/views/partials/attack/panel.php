<?php
/**
 * @var App\Modules\Combat\Models\Fighter $me
 * @var App\Modules\Combat\Models\Fighter $target
 * @var string|null $blockedReason
 * @var bool $awaitingChoice
 * @var list<array{side: string, text: string}> $log
 * @var string|null $message
 * @var bool $lost
 * @var bool $oob
 */
$fighting = $me->attackingUserId === $target->id;
$bar = fn (App\Modules\Combat\Models\Fighter $f) => '<div class="h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-800" role="progressbar" aria-valuenow="' . $f->hpPercent() . '" aria-valuemin="0" aria-valuemax="100" aria-label="' . e($f->username) . ' health">'
    . '<div class="h-full rounded-full ' . ($f->hpPercent() > 30 ? 'bg-green-500' : 'bg-red-500') . '" style="width: ' . $f->hpPercent() . '%"></div></div>';
?>
<section id="attack-panel" class="space-y-6">
    <?php if (!empty($message)): ?>
        <?= $this->component('alert', ['message' => $message, 'type' => !empty($lost) ? 'error' : 'success']) ?>
    <?php endif; ?>
    <?php if (!empty($errors['attack'])): ?>
        <?= $this->component('alert', ['message' => $errors['attack'][0], 'type' => 'error']) ?>
    <?php endif; ?>

    <div class="grid gap-4 sm:grid-cols-2">
        <?php foreach ([$me, $target] as $fighter): ?>
            <div class="card space-y-2">
                <p class="font-medium"><?= e($fighter->username) ?> <span class="text-sm text-gray-500">Level <?= $fighter->level ?></span></p>
                <?= $bar($fighter) ?>
                <p class="text-sm text-gray-500 dark:text-gray-400">Health <?= max(0, $fighter->hp) ?>/<?= $fighter->maxHp ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($log !== []): ?>
        <ol class="card space-y-1 font-mono text-sm" aria-label="Fight log">
            <?php foreach ($log as $line): ?>
                <li class="<?= $line['side'] === 'you' ? 'text-red-700 dark:text-red-400' : 'text-blue-700 dark:text-blue-400' ?>"><?= e($line['text']) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <?php if ($awaitingChoice): ?>
        <div class="card space-y-3">
            <p class="font-semibold">What do you want to do with <?= e($target->username) ?> now?</p>
            <div class="flex flex-wrap gap-2">
                <?php foreach (['mug' => 'Mug them', 'hospitalize' => 'Hospitalize them', 'leave' => 'Leave them'] as $choice => $label): ?>
                    <form method="post" action="/attack/<?= $target->id ?>/finish">
                        <?= csrf_field() ?>
                        <input type="hidden" name="choice" value="<?= $choice ?>">
                        <?= $this->component('button', ['label' => $label, 'type' => 'submit', 'variant' => $choice === 'leave' ? 'secondary' : 'primary']) ?>
                    </form>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif (!empty($lost)): ?>
        <p><a href="/dashboard" class="link">Go home</a></p>
    <?php elseif ($blockedReason !== null): ?>
        <?= $this->component('alert', ['message' => $blockedReason, 'type' => 'warning']) ?>
        <?php if ($fighting): ?>
            <form method="post" action="/attack/<?= $target->id ?>/flee" hx-confirm="Running away counts as losing. Run?">
                <?= csrf_field() ?>
                <?= $this->component('button', ['label' => 'Run away', 'type' => 'submit', 'variant' => 'danger']) ?>
            </form>
        <?php elseif ($me->attackingUserId !== null): ?>
            <p><a href="/attack/<?= $me->attackingUserId ?>" class="link">Go back to that fight</a></p>
        <?php endif; ?>
    <?php elseif ($me->weapons === []): ?>
        <?= $this->component('alert', ['message' => 'You have nothing to fight with. Equip a weapon from your inventory first.', 'type' => 'warning']) ?>
    <?php else: ?>
        <div class="card space-y-3">
            <p class="font-semibold"><?= $fighting ? 'Attack with:' : 'Start the fight (costs ' . $me->attackCost() . ' energy) with:' ?></p>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($me->weapons as $weaponId => $weapon): ?>
                    <form method="post" action="/attack/<?= $target->id ?>"
                          hx-post="/attack/<?= $target->id ?>" hx-target="#attack-panel" hx-swap="outerHTML">
                        <?= csrf_field() ?>
                        <input type="hidden" name="weapon_id" value="<?= $weaponId ?>">
                        <?= $this->component('button', ['label' => $weapon['name'], 'type' => 'submit']) ?>
                    </form>
                <?php endforeach; ?>
            </div>
            <?php if ($fighting): ?>
                <form method="post" action="/attack/<?= $target->id ?>/flee" hx-confirm="Running away counts as losing. Run?">
                    <?= csrf_field() ?>
                    <button type="submit" class="link cursor-pointer text-sm">Run away</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php if (!empty($oob) && isset($player)): ?>
    <?= $this->partial('player-status', ['player' => $player, 'oob' => true]) ?>
<?php endif; ?>
