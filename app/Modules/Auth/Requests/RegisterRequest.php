<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use App\Http\Requests\BaseRequest;

final class RegisterRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9_]+$/'],
            'email' => 'required|email|max:255',
            // bcrypt ignores anything past 72 bytes.
            'password' => 'required|string|min:8|max:72|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Usernames may only contain letters, numbers and underscores.',
            'password.confirmed' => 'The two passwords do not match.',
        ];
    }

    protected function prepareForValidation(array $data): array
    {
        if (is_string($data['username'] ?? null)) {
            $data['username'] = trim($data['username']);
        }

        if (is_string($data['email'] ?? null)) {
            $data['email'] = strtolower(trim($data['email']));
        }

        return $data;
    }
}
