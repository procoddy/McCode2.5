<?php
/*
 * Shared <head> contents. HTMX settings:
 * - responseHandling: also swap 403/404/422 responses so their messages show in the page
 * - allowEval/allowScriptTags off: matches the Content-Security-Policy (no inline JS)
 * - includeIndicatorStyles off: the indicator is styled in app.css
 */
$htmxConfig = [
    'includeIndicatorStyles' => false,
    'allowEval' => false,
    'allowScriptTags' => false,
    'responseHandling' => [
        ['code' => '204', 'swap' => false],
        ['code' => '[23]..', 'swap' => true],
        ['code' => '40[34]|422', 'swap' => true, 'error' => false],
        ['code' => '[45]..', 'swap' => false, 'error' => true],
    ],
];
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?= $this->partial('title') ?>
<meta name="htmx-config" content="<?= e(json_encode($htmxConfig)) ?>">
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="/assets/css/app.css">
<script src="/assets/js/htmx.min.js" defer></script>
<script src="/assets/js/app.js" defer></script>
