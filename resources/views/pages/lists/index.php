<?php
/**
 * @var string $list
 * @var string $title
 * @var string $singular
 * @var list<array<string, mixed>> $entries
 * @var int $listedBy
 * @var list<array{id: int, username: string, total: int}> $mostListed
 * @var string $onlineSince
 * @var string $prefill
 */
$this->set('title', $title);
?>
<h1 class="mb-2 text-2xl font-semibold"><?= e($title) ?></h1>
<p class="mb-1 text-gray-600 dark:text-gray-400"><?= number_format($listedBy) ?> <?= $listedBy === 1 ? 'person has' : 'people have' ?> added you to their <?= e($singular) ?> list.</p>
<?php if ($mostListed !== []): ?>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Most <?= $singular === 'enemy' ? 'hated' : 'liked' ?>:
        <?= implode(' · ', array_map(fn ($u) => '<a class="link" href="/players/' . (int) $u['id'] . '">' . e($u['username']) . '</a>', $mostListed)) ?>
    </p>
<?php endif; ?>

<?= $this->partial('error-summary', ['errors' => $errors ?? []]) ?>

<?php if ($entries === []): ?>
    <div class="card mb-6 text-center text-gray-500 dark:text-gray-400">Your list is empty.</div>
<?php else: ?>
    <div class="mb-6 overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-2 font-semibold">Player</th>
                    <th class="px-4 py-2 font-semibold">Comment</th>
                    <th class="px-4 py-2 font-semibold">Status</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td class="px-4 py-2"><a href="/players/<?= (int) $entry['user_id'] ?>" class="link"><?= e($entry['username']) ?></a> <span class="text-gray-400">#<?= (int) $entry['user_id'] ?></span></td>
                        <td class="px-4 py-2">
                            <form method="post" action="/<?= e($list) ?>/<?= (int) $entry['id'] ?>/comment" class="flex gap-2">
                                <?= csrf_field() ?>
                                <label class="sr-only" for="comment-<?= (int) $entry['id'] ?>">Comment</label>
                                <input id="comment-<?= (int) $entry['id'] ?>" name="comment" maxlength="255" value="<?= e($entry['comment']) ?>" class="input py-1" placeholder="N/A">
                                <button type="submit" class="link cursor-pointer text-xs">Save</button>
                            </form>
                        </td>
                        <td class="px-4 py-2"><?= $this->partial('players.online-dot', ['online' => $entry['last_seen_at'] > $onlineSince]) ?></td>
                        <td class="px-4 py-2">
                            <div class="flex justify-end gap-3">
                                <a href="/mail/compose?to=<?= (int) $entry['user_id'] ?>" class="link">Mail</a>
                                <a href="/attack/<?= (int) $entry['user_id'] ?>" class="link">Attack</a>
                                <form method="post" action="/<?= e($list) ?>/<?= (int) $entry['id'] ?>/remove">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="link cursor-pointer">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<form method="post" action="/<?= e($list) ?>/add" class="card max-w-md space-y-4">
    <?= csrf_field() ?>
    <h2 class="font-semibold">Add <?= $singular === 'enemy' ? 'an enemy' : 'a friend' ?></h2>
    <?= $this->component('form-field', ['name' => 'user_id', 'label' => 'Player ID', 'type' => 'number', 'value' => old('user_id', $prefill), 'errors' => []]) ?>
    <?= $this->component('form-field', ['name' => 'comment', 'label' => 'Comment (optional)', 'value' => old('comment'), 'errors' => []]) ?>
    <?= $this->component('button', ['label' => 'Add', 'type' => 'submit']) ?>
</form>
