<?php
/**
 * Standalone file the player downloads; escaped like every other page.
 *
 * @var string $box
 * @var list<array<string, mixed>> $messages
 */
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title><?= e(ucfirst($box)) ?> archive</title>
<style>body{font-family:sans-serif;margin:2rem}article{border:1px solid #ccc;border-radius:6px;padding:1rem;margin-bottom:1rem}header{color:#555;font-size:.9rem;margin-bottom:.5rem}</style>
</head>
<body>
<h1><?= e(ucfirst($box)) ?> archive</h1>
<?php foreach ($messages as $m): ?>
    <article>
        <header>
            <?= $box === 'inbox' ? 'From' : 'To' ?>: <?= $m['other_id'] ? e("{$m['other_username']} [{$m['other_id']}]") : 'SYSTEM' ?>
            · <?= e(date('F j, Y, g:i:s a', strtotime($m['created_at']))) ?>
        </header>
        <strong><?= e($m['subject']) ?></strong>
        <p><?= nl2br(e($m['body'])) ?></p>
    </article>
<?php endforeach; ?>
<?php if ($messages === []): ?><p>No messages.</p><?php endif; ?>
</body>
</html>
