<?php
/**
 * @var list<array<string, mixed>> $bots
 * @var App\Modules\Players\Models\Player $player
 */
use App\Support\Money;

$this->set('title', 'Battle Tent');
$now = date('Y-m-d H:i:s');
?>
<h1 class="mb-2 text-2xl font-semibold">Battle Tent</h1>
<p class="mb-6 text-gray-600 dark:text-gray-400">Welcome to the battle tent! Here you can challenge NPCs for money.</p>

<?php if ($bots === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">There are no challengers right now.</div>
<?php else: ?>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-2 font-semibold">Bot</th>
                    <th class="px-4 py-2 font-semibold">Level</th>
                    <th class="px-4 py-2 font-semibold">Times beaten</th>
                    <th class="px-4 py-2 font-semibold">Ready?</th>
                    <th class="px-4 py-2 font-semibold">City</th>
                    <th class="px-4 py-2 font-semibold">Bounty</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($bots as $bot): ?>
                    <?php
                    $ready = (int) $bot['hp'] * 2 >= (int) $bot['max_hp']
                        && (int) $bot['city_id'] === $player->cityId
                        && !$player->isHospitalized($now) && !$player->isJailed($now)
                        && !($bot['hospital_until'] && $bot['hospital_until'] > $now)
                        && !($bot['jail_until'] && $bot['jail_until'] > $now);
                    ?>
                    <tr>
                        <td class="px-4 py-2"><a href="/players/<?= (int) $bot['id'] ?>" class="link"><?= e($bot['username']) ?></a></td>
                        <td class="px-4 py-2"><?= (int) $bot['level'] ?></td>
                        <td class="px-4 py-2"><?= number_format((int) $bot['times_beaten']) ?></td>
                        <td class="px-4 py-2"><?= $ready ? '<span class="text-green-600">Yes</span>' : '<span class="text-red-600">No</span>' ?></td>
                        <td class="px-4 py-2"><?= e($bot['city'] ?? '—') ?></td>
                        <td class="px-4 py-2"><?= e(Money::of((int) $bot['reward_money'])->format()) ?></td>
                        <td class="px-4 py-2 text-right">
                            <?php if ($bot['beaten_by_me']): ?>
                                <span class="text-gray-500 italic">Already beaten</span>
                            <?php else: ?>
                                <a href="/attack/<?= (int) $bot['id'] ?>" class="btn btn-secondary px-3 py-1 text-xs">Challenge</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
