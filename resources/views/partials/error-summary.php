<?php
/**
 * First message of each validation error, for pages whose forms have no
 * per-field error slots (e.g. one small form per table row).
 *
 * @var array<string, list<string>> $errors
 */
?>
<?php foreach (array_unique(array_map(fn (array $messages) => $messages[0], $errors ?? [])) as $error): ?>
    <?= $this->component('alert', ['message' => $error, 'type' => 'error']) ?>
<?php endforeach; ?>
