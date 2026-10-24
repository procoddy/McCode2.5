<?php

declare(strict_types=1);

namespace App\Modules\Auth\Requests;

use App\Http\Requests\BaseRequest;

final class LoginRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'login' => 'required|string|max:255',
            'password' => 'required|string|max:1024',
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Enter your username or email address.',
        ];
    }
}
