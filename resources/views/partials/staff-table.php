<?php
/**
 * Simple data table for staff pages. Cells are escaped unless they are an
 * array ['html' => '...'] built from trusted, already-escaped markup.
 *
 * @var list<string> $headings
 * @var list<list<string|int|float|null|array{html: string}>> $rows
 * @var string $empty
 */
?>
<?php if ($rows === []): ?>
    <div class="card text-center text-gray-500 dark:text-gray-400"><?= e($empty ?? 'Nothing here yet.') ?></div>
<?php else: ?>
    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                <tr class="*:px-4 *:py-3 *:font-medium">
                    <?php foreach ($headings as $heading): ?><th><?= e($heading) ?></th><?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                <?php foreach ($rows as $row): ?>
                    <tr class="*:px-4 *:py-2.5 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <?php foreach ($row as $cell): ?>
                            <td><?= is_array($cell) ? $cell['html'] : e($cell ?? '') ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
