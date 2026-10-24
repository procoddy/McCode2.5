<?php
/**
 * @var string $message
 * @var string $type success|error|info|warning (default info)
 */
?>
<div class="alert alert-<?= e($type ?? 'info') ?>" role="<?= ($type ?? '') === 'error' ? 'alert' : 'status' ?>">
    <?= e($message) ?>
</div>
