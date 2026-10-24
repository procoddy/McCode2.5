<?php

declare(strict_types=1);

namespace App\Legacy;

use App\Core\Session;

/**
 * Keeps the McCodes `$_SESSION['userid']` / `$_SESSION['loggedin']` keys in
 * step with the new session so users stay logged in across old and new pages.
 *
 * @deprecated Remove once no legacy page reads these keys.
 */
final class LegacySessionAdapter
{
    public function __construct(private readonly Session $session)
    {
    }

    /** Call before including a legacy page. */
    public function syncToLegacy(): void
    {
        $userId = $this->session->get('user_id');

        $this->session->put('loggedin', $userId !== null ? 1 : 0);
        $this->session->put('userid', $userId !== null ? (int) $userId : 0);
    }

    /** Call after a legacy login/logout script runs. */
    public function syncFromLegacy(): void
    {
        if ((int) $this->session->get('loggedin', 0) === 1 && (int) $this->session->get('userid', 0) > 0) {
            $this->session->put('user_id', (int) $this->session->get('userid'));
        } else {
            $this->session->forget('user_id');
        }
    }
}
