<?php

declare(strict_types=1);

namespace App\Modules\Auth\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Modules\Auth\Contracts\AuthRepositoryInterface;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Services\AuthService;

final class AuthController
{
    public function __construct(private readonly AuthService $auth, private readonly Session $session)
    {
    }

    public function showLogin(): Response
    {
        return view('pages.auth.login', [], 'guest');
    }

    public function login(LoginRequest $form, Request $request): Response
    {
        if (!$this->auth->attempt($form->input('login'), $form->input('password'), $request->ip())) {
            throw ValidationException::withMessages(['login' => 'These credentials do not match our records.']);
        }

        return Response::redirect($this->auth->pullIntendedUrl());
    }

    public function showRegister(): Response
    {
        return view('pages.auth.register', [], 'guest');
    }

    public function register(RegisterRequest $form, Request $request): Response
    {
        $user = $this->auth->register(
            $form->input('username'),
            $form->input('email'),
            $form->input('password'),
            $request->ip(),
        );

        $this->session->flash('status', "Welcome to the game, {$user->username}!");

        return Response::redirect('/dashboard');
    }

    /**
     * HTMX live check for the register form (legacy checkun.php / checkem.php).
     * hx-params sends only the field being typed in.
     */
    public function checkAvailability(Request $request, AuthRepositoryInterface $users, Validator $validator): Response
    {
        $field = $request->has('email') ? 'email' : 'username';
        $value = trim((string) $request->input($field, ''));
        $rules = (new RegisterRequest($request, $validator))->rules();

        try {
            $validator->validate([$field => $field === 'email' ? strtolower($value) : $value], [$field => $rules[$field]]);
            $taken = $field === 'email' ? $users->emailExists(strtolower($value)) : $users->usernameExists($value);
            $result = $taken
                ? ['ok' => false, 'message' => $field === 'email' ? 'Already registered.' : 'Already taken.']
                : ['ok' => true, 'message' => 'Available.'];
        } catch (ValidationException $e) {
            $result = ['ok' => false, 'message' => $e->errors()[$field][0]];
        }

        return view('partials.auth.availability', $result, layout: null);
    }

    public function logout(): Response
    {
        $this->auth->logout();
        $this->session->flash('status', 'You have been logged out.');

        return Response::redirect('/login');
    }
}
