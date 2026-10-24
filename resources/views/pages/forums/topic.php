<?php
/**
 * @var array<string, mixed> $topic
 * @var App\Support\Pagination $posts
 * @var bool $isModerator
 * @var string|null $banReason
 * @var list<array<string, mixed>> $forums
 * @var App\Modules\Players\Models\Player $player
 */
$this->set('title', $topic['title']);
$mod = function (string $action, string $label, string $confirm = '') use ($topic): string {
    return '<form method="post" action="/forums/topics/' . (int) $topic['id'] . '/' . $action . '"' . ($confirm ? ' hx-confirm="' . e($confirm) . '"' : '') . '>'
        . csrf_field() . '<button type="submit" class="btn btn-secondary px-3 py-1 text-xs">' . e($label) . '</button></form>';
};
?>
<div class="mb-2 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold"><?= e($topic['title']) ?></h1>
    <a href="/forums/<?= (int) $topic['forum_id'] ?>" class="link">&larr; <?= e($topic['forum_name']) ?></a>
</div>
<?php if ($topic['description'] !== ''): ?><p class="mb-4 text-gray-600 dark:text-gray-400"><?= e($topic['description']) ?></p><?php endif; ?>

<?php if ($isModerator): ?>
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <?= $mod($topic['pinned'] ? 'unpin' : 'pin', $topic['pinned'] ? 'Unpin' : 'Pin') ?>
        <?= $mod($topic['locked'] ? 'unlock' : 'lock', $topic['locked'] ? 'Unlock' : 'Lock') ?>
        <?= $mod('delete', 'Delete topic', 'Delete this topic and all its posts?') ?>
        <?php if (count($forums) > 1): ?>
            <form method="post" action="/forums/topics/<?= (int) $topic['id'] ?>/move" class="flex items-center gap-2">
                <?= csrf_field() ?>
                <label class="sr-only" for="move-to">Move to</label>
                <select id="move-to" name="forum_id" class="input py-1 text-xs">
                    <?php foreach ($forums as $f): ?>
                        <?php if ((int) $f['id'] !== (int) $topic['forum_id']): ?><option value="<?= (int) $f['id'] ?>"><?= e($f['name']) ?></option><?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-secondary px-3 py-1 text-xs">Move</button>
            </form>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="space-y-4">
    <?php foreach ($posts->items as $post): ?>
        <article id="post-<?= (int) $post['id'] ?>" class="card grid gap-4 sm:grid-cols-[10rem_1fr]">
            <aside class="text-sm">
                <?php if ($post['user_id']): ?>
                    <a href="/players/<?= (int) $post['user_id'] ?>" class="link font-medium"><?= e($post['author']) ?></a>
                    <?php if ($post['forum_avatar_url']): ?><img src="<?= e($post['forum_avatar_url']) ?>" alt="" class="my-2 size-16 rounded object-cover"><?php endif; ?>
                    <p class="text-gray-500">Level <?= (int) $post['level'] ?> · <?= number_format((int) $post['author_posts']) ?> posts</p>
                <?php else: ?>
                    <span class="text-gray-500">Deleted player</span>
                <?php endif; ?>
            </aside>
            <div class="min-w-0">
                <p class="mb-2 text-xs text-gray-500"><?= e(date('F j, Y, g:i a', strtotime($post['created_at']))) ?></p>
                <div class="break-words whitespace-pre-line"><?= e($post['body']) ?></div>
                <?php if ($post['edit_count'] > 0): ?>
                    <p class="mt-2 text-xs text-gray-500">Edited <?= (int) $post['edit_count'] ?> time(s), last by <?= e($post['editor'] ?? '—') ?> on <?= e(date('M j, g:i a', strtotime($post['edited_at']))) ?></p>
                <?php endif; ?>
                <?php if ($post['forum_signature']): ?>
                    <p class="mt-3 border-t border-gray-200 pt-2 text-sm text-gray-500 dark:border-gray-800"><?= e($post['forum_signature']) ?></p>
                <?php endif; ?>
                <div class="mt-3 flex gap-3 text-sm">
                    <?php if ($isModerator || ((int) $post['user_id'] === $player->id && !$topic['locked'])): ?>
                        <a href="/forums/posts/<?= (int) $post['id'] ?>/edit" class="link">Edit</a>
                    <?php endif; ?>
                    <?php if ($isModerator): ?>
                        <form method="post" action="/forums/posts/<?= (int) $post['id'] ?>/delete" hx-confirm="Delete this post?">
                            <?= csrf_field() ?>
                            <button type="submit" class="link cursor-pointer text-red-600">Delete</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
<?= $this->component('pagination', ['paginator' => $posts, 'url' => "/forums/topics/{$topic['id']}"]) ?>

<?php if ($banReason !== null): ?>
    <?= $this->component('alert', ['message' => $banReason, 'type' => 'error']) ?>
<?php elseif ($topic['locked'] && !$isModerator): ?>
    <p class="mt-6 text-gray-500">This topic is locked.</p>
<?php else: ?>
    <form method="post" action="/forums/topics/<?= (int) $topic['id'] ?>/reply" class="card mt-6 space-y-4">
        <?= csrf_field() ?>
        <?= $this->component('form-field', ['name' => 'body', 'label' => 'Reply', 'type' => 'textarea', 'value' => old('body')]) ?>
        <?= $this->component('button', ['label' => 'Post reply', 'type' => 'submit']) ?>
    </form>
<?php endif; ?>
