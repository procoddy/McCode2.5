<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;
use App\Http\Responses\HtmlResponse;

if (!function_exists('app')) {
    function app(?string $abstract = null): mixed
    {
        $app = Application::getInstance();

        return $abstract === null ? $app : $app->container()->make($abstract);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Application::getInstance()->config($key, $default);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return Application::getInstance()->basePath($path);
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('view')) {
    /**
     * Render a page inside a layout. For HTMX requests only the page content is
     * returned (layouts/fragment.php) and swapped into #content, unless the page
     * uses a different layout than the one on screen, in which case the browser
     * is told to load it as a full page.
     *
     * Pass $layout = null to render a bare partial, e.g. for hx-target updates.
     */
    function view(string $template, array $data = [], ?string $layout = 'app', int $status = 200): HtmlResponse
    {
        $container = Application::getInstance()->container();
        $request = $container->has(Request::class) ? $container->make(Request::class) : null;
        $headers = [];

        if ($layout !== null && $request?->wantsFragment()) {
            if ($request->header('X-Layout') !== $layout) {
                app(Session::class)->reflash();

                return new HtmlResponse('', 200, ['HX-Redirect' => $request->fullPath()]);
            }

            if ($request->isBoosted()) {
                $headers['HX-Push-Url'] = $request->fullPath();
            }

            $layout = 'fragment';
        }

        $html = app(View::class)->render($template, $data, $layout);

        return new HtmlResponse($html, $status, $headers);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return app(Session::class)->token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return app(Session::class)->get('_old_input', [])[$key] ?? $default;
    }
}
