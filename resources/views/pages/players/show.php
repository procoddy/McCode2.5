<?php
/**
 * @var array<string, mixed> $profile
 * @var App\Modules\Players\Models\Player $viewer
 * @var list<string> $staffRoles
 * @var bool $canEditPlayers
 * @var bool $canSeeIps
 * @var bool $transfersOn
 * @var string $now
 * @var string $onlineSince
 */
$this->set('title', $profile['username']);
$id = (int) $profile['id'];
$isSelf = $id === $viewer->id;
$date = fn (?string $d, string $f = 'F j, Y, g:i a') => $d ? date($f, strtotime($d)) : 'Never';
$daysOld = (int) floor((strtotime($now) - strtotime($profile['created_at'])) / 86400);
$isDonator = $profile['donator_until'] && $profile['donator_until'] > $now;
$dl = fn (array $rows) => implode('', array_map(
    fn ($label, $value) => '<dt class="text-gray-500 dark:text-gray-400">' . e($label) . '</dt><dd>' . (is_array($value) ? $value['html'] : e($value)) . '</dd>',
    array_keys($rows),
    $rows,
));
$statuses = array_filter([
    $profile['fed_until'] && $profile['fed_until'] > $now ? 'In federal jail until ' . $date($profile['fed_until']) . '. ' . $profile['fed_reason'] : null,
    $profile['hospital_until'] && $profile['hospital_until'] > $now ? 'In hospital until ' . $date($profile['hospital_until'], 'g:i a') . '. ' . $profile['hospital_reason'] : null,
    $profile['jail_until'] && $profile['jail_until'] > $now ? 'In jail until ' . $date($profile['jail_until'], 'g:i a') . '. ' . $profile['jail_reason'] : null,
]);
?>
<div class="mb-6 flex flex-wrap items-center gap-4">
    <?php if ($profile['avatar_url']): ?>
        <img src="<?= e($profile['avatar_url']) ?>" alt="" class="size-16 rounded-full object-cover">
    <?php endif; ?>
    <div>
        <h1 class="text-2xl font-semibold">
            <?= e($profile['username']) ?> <span class="text-gray-400">#<?= $id ?></span>
            <?php if ($isDonator): ?><span title="Donator" class="text-brand-600">&#9733;</span><?php endif; ?>
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            <?= e($staffRoles === [] ? ($profile['account_type'] === 'NPC' ? 'NPC' : 'Member') : implode(', ', $staffRoles)) ?>
            · <?= $this->partial('players.online-dot', ['online' => $profile['last_seen_at'] > $onlineSince]) ?>
        </p>
    </div>
</div>

<?php foreach ($statuses as $status): ?>
    <?= $this->component('alert', ['message' => $status, 'type' => 'warning']) ?>
<?php endforeach; ?>

<div class="grid gap-6 lg:grid-cols-3">
    <section class="card">
        <h2 class="mb-3 font-semibold">General</h2>
        <dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1.5 text-sm">
            <?= $dl([
                'Gender' => $profile['gender'],
                'Signed up' => $date($profile['created_at'], 'F j, Y'),
                'Days old' => number_format($daysOld),
                'Last active' => $date($profile['last_seen_at']),
                'Last login' => $date($profile['last_login_at']),
                'Location' => $profile['city'] ?? '—',
                ...($staffRoles !== [] && $profile['duties'] !== 'N/A' ? ['Duties' => $profile['duties']] : []),
            ]) ?>
        </dl>
    </section>

    <section class="card">
        <h2 class="mb-3 font-semibold">Stats</h2>
        <dl class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1.5 text-sm">
            <?= $dl([
                'Level' => $profile['level'],
                'Health' => "{$profile['hp']}/{$profile['max_hp']}",
                'Money' => App\Support\Money::of((int) $profile['money'])->format(),
                'Crystals' => number_format((int) $profile['crystals']),
                'Property' => $profile['house'] ?? '—',
                'Gang' => $profile['gang'] ?? 'None',
                'Referrals' => $profile['referrals'],
                'Friends' => $profile['friends'],
                'Enemies' => $profile['enemies'],
            ]) ?>
        </dl>
    </section>

    <section class="card space-y-2">
        <h2 class="mb-1 font-semibold">Actions</h2>
        <?php if ($isSelf): ?>
            <p class="text-sm text-gray-500">This is you.</p>
        <?php else: ?>
            <?php if ($transfersOn): ?>
                <?php if ($viewer->bankBalance !== null && $profile['has_bank']): ?>
                    <a class="btn btn-secondary w-full" href="/players/<?= $id ?>/send/bank">Bank transfer</a>
                <?php endif; ?>
                <?php if ($viewer->cyberBalance !== null && $profile['has_cyber']): ?>
                    <a class="btn btn-secondary w-full" href="/players/<?= $id ?>/send/cyber">Cyber Bank transfer</a>
                <?php endif; ?>
            <?php endif; ?>
            <a class="btn btn-primary w-full" href="/attack/<?= $id ?>">Attack</a>
            <a class="btn btn-secondary w-full" href="/mail/compose?to=<?= $id ?>">Send mail</a>
            <a class="btn btn-secondary w-full" href="/friends?add=<?= $id ?>">Add to friends</a>
            <a class="btn btn-secondary w-full" href="/enemies?add=<?= $id ?>">Add to black list</a>
            <a class="btn btn-secondary w-full" href="/players/<?= $id ?>/report">Report player</a>
        <?php endif; ?>
        <?php if ($canEditPlayers): ?>
            <a class="btn btn-secondary w-full" href="/admin/users/<?= $id ?>/edit" hx-boost="false">Edit in staff panel</a>
        <?php endif; ?>
    </section>
</div>

<?php if ($canSeeIps || $canEditPlayers): ?>
    <section class="card mt-6">
        <h2 class="mb-3 font-semibold">Staff info</h2>
        <?php if ($canSeeIps): ?>
            <dl class="mb-4 grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1.5 text-sm">
                <?= $dl(['Last login IP' => $profile['last_login_ip'] ?? 'N/A', 'Signup IP' => $profile['signup_ip'] ?? 'N/A']) ?>
            </dl>
        <?php endif; ?>
        <?php if ($canEditPlayers): ?>
            <form method="post" action="/admin/users/<?= $id ?>/notes" class="space-y-3">
                <?= csrf_field() ?>
                <?= $this->component('form-field', ['name' => 'staff_notes', 'label' => 'Staff notes', 'type' => 'textarea', 'value' => $profile['staff_notes'] ?? '']) ?>
                <?= $this->component('button', ['label' => 'Save notes', 'type' => 'submit', 'variant' => 'secondary']) ?>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>
