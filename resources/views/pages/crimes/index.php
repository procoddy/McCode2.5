<?php
/**
 * @var list<App\Modules\Crimes\Models\CrimeGroup> $groups
 * @var App\Modules\Crimes\Services\CrimeService $crimeService
 * @var App\Modules\Players\Models\Player $player
 * @var string|null $blockedReason
 */
$this->set('title', 'Criminal Centre');
?>
<h1 class="mb-6 text-2xl font-semibold">Criminal Centre</h1>

<?php if ($blockedReason !== null): ?>
    <?= $this->component('alert', ['message' => $blockedReason, 'type' => 'warning']) ?>
<?php endif; ?>

<div id="crime-result" aria-live="polite"></div>

<?php if ($groups === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">No crimes have been set up yet.</div>
<?php endif; ?>

<div class="space-y-6">
    <?php foreach ($groups as $group): ?>
        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
            <h2 class="border-b border-gray-200 bg-gray-50 px-4 py-2 font-semibold dark:border-gray-800 dark:bg-gray-800/50"><?= e($group->name) ?></h2>
            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($group->crimes as $crime): ?>
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="font-medium"><?= e($crime->name) ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= $crime->braveCost ?> brave · <?= e(round($crimeService->successChance($player, $crime))) ?>% chance
                            </p>
                        </div>
                        <?php if ($blockedReason === null): ?>
                            <form method="post" action="/crimes/<?= $crime->id ?>"
                                  hx-post="/crimes/<?= $crime->id ?>" hx-target="#crime-result" hx-swap="innerHTML">
                                <?= csrf_field() ?>
                                <?= $this->component('button', ['label' => 'Do', 'type' => 'submit', 'variant' => 'secondary']) ?>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</div>
