<?php
/** @var array<string, int> $stats */
$this->set('title', 'Game statistics');
$money = fn (int $n) => App\Support\Money::of($n)->format();
$avg = fn (int $total, int $count) => intdiv($total, max(1, $count));
$cards = [
    'Players' => number_format($stats['players']) . " ({$stats['male']} male, {$stats['female']} female)",
    'Cash in circulation' => $money($stats['money']),
    'Average cash per player' => $money($avg($stats['money'], $stats['players'])),
    'Money in banks' => $money($stats['banked']),
    'Players with a bank account' => number_format($stats['bank_accounts']),
    'Average bank balance' => $money($avg($stats['banked'], $stats['bank_accounts'])),
    'Crystals in circulation' => number_format($stats['crystals']),
    'Average crystals per player' => number_format($avg($stats['crystals'], $stats['players'])),
    'Items in circulation' => number_format($stats['items']),
    'Mails sent' => number_format($stats['mail']),
    'Events sent' => number_format($stats['events']),
];
?>
<h1 class="mb-6 text-2xl font-semibold">Game statistics</h1>

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($cards as $label => $value): ?>
        <div class="card">
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($label) ?></p>
            <p class="mt-1 text-xl font-semibold"><?= e($value) ?></p>
        </div>
    <?php endforeach; ?>
</div>
