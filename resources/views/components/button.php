<?php
/**
 * @var string $label
 * @var string $type    button|submit|reset (default button)
 * @var string $variant primary|secondary|danger (default primary)
 * @var bool   $block   Full width
 * @var array  $attrs   Extra attributes, e.g. ['hx-post' => '/bank/deposit']
 */
$classes = 'btn btn-' . ($variant ?? 'primary') . (!empty($block) ? ' w-full' : '');
$extra = '';
foreach ($attrs ?? [] as $attr => $value) {
    $extra .= ' ' . e($attr) . '="' . e($value) . '"';
}
?>
<button type="<?= e($type ?? 'button') ?>" class="<?= e($classes) ?>"<?= $extra ?>><?= e($label) ?></button>
