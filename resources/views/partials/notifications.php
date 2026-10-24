<?php $status = app(App\Core\Session::class)->get('status'); ?>
<div id="notifications" aria-live="polite">
    <?php if ($status): ?>
        <?= $this->component('alert', ['message' => $status, 'type' => 'success']) ?>
    <?php endif; ?>
</div>
