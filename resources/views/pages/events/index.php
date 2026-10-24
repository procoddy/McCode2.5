<?php /** @var App\Support\Pagination $events */ $this->set('title', 'Events'); ?>
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-semibold">Events</h1>
    <?php if ($events->items !== []): ?>
        <form method="post" action="/events/clear" hx-confirm="Delete all your events?">
            <?= csrf_field() ?>
            <?= $this->component('button', ['label' => 'Delete all', 'type' => 'submit', 'variant' => 'secondary']) ?>
        </form>
    <?php endif; ?>
</div>

<?php if ($events->items === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400">No events yet.</div>
<?php else: ?>
    <ol class="card divide-y divide-gray-200 p-0 dark:divide-gray-800">
        <?php foreach ($events->items as $event): ?>
            <li class="flex flex-wrap items-start justify-between gap-2 px-4 py-3 text-sm">
                <span>
                    <?php if ($event['read_at'] === null): ?>
                        <span class="mr-1 rounded-full bg-brand-600 px-2 py-0.5 text-xs font-semibold text-white">New</span>
                    <?php endif; ?>
                    <?= e($event['body']) ?>
                </span>
                <time class="text-gray-500 dark:text-gray-400"><?= e(date('M j, g:i a', strtotime($event['created_at']))) ?></time>
            </li>
        <?php endforeach; ?>
    </ol>
    <?= $this->component('pagination', ['paginator' => $events, 'url' => '/events']) ?>
<?php endif; ?>
