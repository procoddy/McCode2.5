<?php
/*
 * Used instead of the full layout for HTMX requests: only what goes inside
 * #content. HTMX moves the <title> into the browser tab.
 */
?>
<?= $this->partial('title') ?>
<?= $this->partial('notifications') ?>
<?= $content ?>
