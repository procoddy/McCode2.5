<?php

declare(strict_types=1);

namespace App\Modules\Auth\Services;

use App\Contracts\LoggerInterface;
use App\Core\Session;
use App\Exceptions\ValidationException;
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Models\User;

final class AuthService
{
    private const SESSION_KEY = 'user_id';

    /** bcrypt hash of a random string nobody knows. */
    private const DUMMY_HASH = '$2y$10$DOupCwwoT7dS6iGsdkVRfeupPgvjFxOcM9ezBe63m4Zd/CFpP7a0e';

    private ?User $user = null;

    public function __construct(
        private readonly AuthRepositoryInterface $users,
        private readonly Session $session,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function attempt(string $login, string $password, string $ip): bool
    {
        $user = $this->users->findByLogin($login);

        // Verify against a dummy hash for unknown users so response timing
        // does not reveal which usernames exist.
        $hash = $user?->passwordHash ?? self::DUMMY_HASH;

        if (!password_verify($password, $hash) || $user === null) {
            $this->logger->info('Failed login attempt', ['login' => $login, 'ip' => $ip]);

            return false;
        }

        $options = config('security.password', []);
        if (password_needs_rehash($user->passwordHash, $options['algo'] ?? PASSWORD_DEFAULT, $options['options'] ?? [])) {
            $this->users->updatePasswordHash($user->id, $this->hash($password));
        }

        $this->login($user);
        $this->users->recordLogin($user->id, $ip);

        // A fresh login supersedes any pending staff "force logout".
        if ($user->forceLogout) {
            $this->users->clearForceLogout($user->id);
        }

        return true;
    }

    /**
     * Create an account and log the new player straight in.
     *
     * @throws ValidationException when the username or email is taken
     */
    public function register(string $username, string $email, string $password, string $ip): User
    {
        $errors = [];

        if ($this->users->usernameExists($username)) {
            $errors['username'] = 'That username is already taken.';
        }

        if ($this->users->emailExists($email)) {
            $errors['email'] = 'An account with that email address already exists.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $user = $this->users->create([
            'username' => $username,
            'email' => $email,
            'password_hash' => $this->hash($password),
            'signup_ip' => $ip,
        ]);

        $this->login($user);
        $this->users->recordLogin($user->id, $ip);
        $this->logger->info('User registered', ['user_id' => $user->id, 'ip' => $ip]);

        return $user;
    }

    public function login(User $user): void
    {
        // New session id and CSRF token prevent session fixation.
        $this->session->regenerate();
        $this->session->regenerateToken();
        $this->session->put(self::SESSION_KEY, $user->id);
        $this->user = $user;
    }

    public function logout(): void
    {
        $this->session->invalidate();
        $this->session->regenerateToken();
        $this->user = null;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): ?int
    {
        return $this->user()?->id;
    }

    public function user(): ?User
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $id = $this->session->get(self::SESSION_KEY);

        if ($id === null) {
            return null;
        }

        $this->user = $this->users->findById((int) $id);

        // Account was deleted while the session was alive.
        if ($this->user === null) {
            $this->session->forget(self::SESSION_KEY);
        }

        // Staff used "force logout" (legacy users.force_logout).
        if ($this->user?->forceLogout) {
            $this->users->clearForceLogout($this->user->id);
            $this->logout();

            return null;
        }

        return $this->user;
    }

    public function hash(string $password): string
    {
        $options = config('security.password', []);

        return password_hash($password, $options['algo'] ?? PASSWORD_DEFAULT, $options['options'] ?? []);
    }

    public function rememberIntendedUrl(string $path): void
    {
        $this->session->put('url.intended', $path);
    }

    public function pullIntendedUrl(string $default = '/dashboard'): string
    {
        return $this->session->pull('url.intended', $default);
    }
}
