<?php

declare(strict_types=1);

namespace App\Http\Concerns;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Exceptions\ValidationException;

/**
 * For forms that HTMX posts into a panel (hx-target="#x-panel" hx-swap="outerHTML").
 *
 * - HTMX, success: the panel re-rendered with a success message.
 * - HTMX, ValidationException: the panel with errors and the submitted input, status 422.
 * - No JavaScript: flash the message and redirect, or the normal redirect-back with errors.
 */
trait RespondsWithPanel
{
    /**
     * @param callable(): string $action Performs the change and returns the success message.
     * @param callable(array, int): Response $panel Renders the panel with extra data and a status.
     */
    private function panelAction(Request $request, string $redirectTo, callable $action, callable $panel): Response
    {
        try {
            $message = $action();
        } catch (ValidationException $e) {
            if (!$request->isHtmx()) {
                throw $e;
            }

            // Shared so form-field components inside the panel find their messages.
            app(View::class)->share('errors', $e->errors());

            return $panel(['errors' => $e->errors(), 'input' => $request->all()], 422);
        }

        if (!$request->isHtmx()) {
            app(Session::class)->flash('status', $message);

            return Response::redirect($redirectTo);
        }

        return $panel(['message' => $message], 200);
    }
}
