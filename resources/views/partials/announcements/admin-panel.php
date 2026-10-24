<?php
/**
 * Returned on its own for hx-post requests and swapped over itself.
 *
 * @var App\Support\Pagination $announcements
 * @var array<string, list<string>> $errors
 * @var string $body
 * @var string|null $message
 */
?>
<section id="announcements-panel" class="space-y-6">
    <form method="post" action="/admin/announcements" class="card space-y-4"
          hx-post="/admin/announcements" hx-target="#announcements-panel" hx-swap="outerHTML">
        <?= csrf_field() ?>

        <?php if (!empty($message)): ?>
            <?= $this->component('alert', ['message' => $message, 'type' => 'success']) ?>
        <?php endif; ?>

        <?= $this->component('form-field', [
            'name' => 'body',
            'label' => 'New announcement',
            'type' => 'textarea',
            'value' => $body ?: old('body'),
            'errors' => $errors['body'] ?? [],
            'hint' => 'Plain text. Line breaks are kept. Every player sees it as unread.',
        ]) ?>

        <?= $this->component('button', ['label' => 'Post announcement', 'type' => 'submit']) ?>
    </form>

    <div>
        <h2 class="mb-3 text-lg font-semibold">Latest</h2>
        <?php if ($announcements->items === []): ?>
            <p class="text-gray-500 dark:text-gray-400">Nothing posted yet.</p>
        <?php else: ?>
            <ol class="space-y-4">
                <?php foreach ($announcements->items as $announcement): ?>
                    <?= $this->partial('announcements.item', ['announcement' => $announcement, 'isNew' => false]) ?>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</section>
