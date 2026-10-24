<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ApplicationException;
use Throwable;

final class View
{
    private array $shared = [];

    public function __construct(private readonly string $path)
    {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    /**
     * Called from a page template to pass a value up to its layout,
     * e.g. <?php $this->set('title', 'Bank') ?>. Plain variables set in a
     * page do not reach the layout because each file renders in its own scope.
     */
    public function set(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function exists(string $view): bool
    {
        return is_file($this->resolve($view));
    }

    /**
     * Render a page, optionally wrapped in resources/views/layouts/{$layout}.php.
     * The layout receives the rendered page as $content.
     */
    public function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = $this->renderFile($view, $data);

        if ($layout === null) {
            return $content;
        }

        return $this->renderFile("layouts.{$layout}", [...$data, 'content' => $content]);
    }

    public function component(string $name, array $props = []): string
    {
        return $this->renderFile("components.{$name}", $props);
    }

    public function partial(string $name, array $data = []): string
    {
        return $this->renderFile("partials.{$name}", $data);
    }

    private function resolve(string $view): string
    {
        return $this->path . '/' . str_replace('.', '/', $view) . '.php';
    }

    private function renderFile(string $view, array $data): string
    {
        $__file = $this->resolve($view);

        if (!is_file($__file)) {
            throw new ApplicationException("View [{$view}] not found.");
        }

        extract([...$this->shared, ...$data], EXTR_SKIP);

        ob_start();

        try {
            include $__file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
