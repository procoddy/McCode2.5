<?php
/**
 * @var list<array<string, mixed>> $bots
 * @var array<int, string> $npcs
 */
use App\Support\Money;

$this->set('title', 'Challenge bots');
?>
<h1 class="mb-2 text-2xl font-semibold">Battle tent challenge bots</h1>
<p class="mb-6 text-sm text-gray-500 dark:text-gray-400">NPC players listed in the battle tent. Each player earns the bounty the first time they beat a bot.</p>

<?php if ($bots !== []): ?>
    <div class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-2 font-semibold">Bot</th>
                    <th class="px-4 py-2 font-semibold">Level</th>
                    <th class="px-4 py-2 font-semibold">Times beaten</th>
                    <th class="px-4 py-2 font-semibold">Bounty</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($bots as $bot): ?>
                    <tr>
                        <td class="px-4 py-2"><?= e($bot['username']) ?> <span class="text-gray-400">#<?= (int) $bot['user_id'] ?></span></td>
                        <td class="px-4 py-2"><?= (int) $bot['level'] ?></td>
                        <td class="px-4 py-2"><?= number_format((int) $bot['times_beaten']) ?></td>
                        <td class="px-4 py-2">
                            <form method="post" action="/admin/challenge-bots/<?= (int) $bot['user_id'] ?>" class="flex items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="PUT">
                                <label class="sr-only" for="reward-<?= (int) $bot['user_id'] ?>">Bounty</label>
                                <input id="reward-<?= (int) $bot['user_id'] ?>" type="number" min="1" name="reward_money" value="<?= (int) $bot['reward_money'] ?>" class="input w-32 py-1">
                                <button type="submit" class="link cursor-pointer text-xs">Save</button>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="post" action="/admin/challenge-bots/<?= (int) $bot['user_id'] ?>" hx-confirm="Remove this challenge bot?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="link cursor-pointer text-red-600">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<form method="post" action="/admin/challenge-bots" class="card max-w-md space-y-4">
    <?= csrf_field() ?>
    <h2 class="font-semibold">Add a challenge bot</h2>
    <?php if ($npcs === []): ?>
        <p class="text-sm text-gray-500">No NPC players are available. Set a player's account type to NPC in <a class="link" href="/admin/users">Find &amp; edit players</a> first.</p>
    <?php else: ?>
        <?= $this->component('form-field', ['name' => 'user_id', 'label' => 'NPC', 'type' => 'select', 'options' => $npcs, 'value' => old('user_id')]) ?>
        <?= $this->component('form-field', ['name' => 'reward_money', 'label' => 'Bounty for beating', 'type' => 'number', 'value' => old('reward_money')]) ?>
        <?= $this->component('button', ['label' => 'Add challenge bot', 'type' => 'submit']) ?>
    <?php endif; ?>
</form>
