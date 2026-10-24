<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\LoggerInterface;
use App\Exceptions\ApplicationException;
use App\Exceptions\ValidationException;
use App\Http\Responses\HtmlResponse;
use Throwable;

final class ExceptionHandler
{
    private const SENSITIVE_INPUT = ['password', 'password_confirmation', '_token'];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly View $view,
        private readonly Session $session,
        private readonly bool $debug = false,
    ) {
    }

    public function render(Throwable $e, Request $request): Response
    {
        if ($e instanceof ValidationException) {
            return $this->renderValidation($e, $request);
        }

        $status = $e instanceof ApplicationException ? $e->statusCode() : 500;

        if ($status >= 500) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e::class,
                'file' => $e->getFile() . ':' . $e->getLine(),
                'path' => $request->path(),
            ]);
        }

        $message = $status < 500 || $this->debug ? $e->getMessage() : 'Server Error';

        if ($request->expectsJson()) {
            return Response::json(['message' => $message], $status);
        }

        $template = $this->view->exists("errors.{$status}") ? "errors.{$status}" : 'errors.500';

        try {
            $html = $this->view->render($template, [
                'status' => $status,
                'message' => $message,
                'exception' => $this->debug ? $e : null,
            ], $request->wantsFragment() ? 'fragment' : 'guest');
        } catch (Throwable) {
            $html = '<h1>' . $status . '</h1><p>' . e($message) . '</p>';
        }

        return new HtmlResponse($html, $status);
    }

    private function renderValidation(ValidationException $e, Request $request): Response
    {
        if ($request->expectsJson()) {
            return Response::json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        }

        $this->session->flash('_errors', $e->errors());
        $this->session->flash('_old_input', array_diff_key($request->all(), array_flip(self::SENSITIVE_INPUT)));

        return Response::redirect($this->previousPath($request));
    }

    /**
     * Only the path of the Referer is used so a forged header cannot redirect off-site.
     */
    private function previousPath(Request $request): string
    {
        $path = parse_url((string) $request->referer(), PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/') && !str_starts_with($path, '//') ? $path : '/';
    }
}
