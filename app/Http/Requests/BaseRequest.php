<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Core\Request;
use App\Core\Validator;
use App\Exceptions\AuthorizationException;

/**
 * Type-hint a subclass in a controller action and the router validates it
 * before the action runs.
 */
abstract class BaseRequest
{
    private array $validated = [];

    public function __construct(protected readonly Request $request, protected readonly Validator $validator)
    {
    }

    /** @return array<string, string|list<string>> */
    abstract public function rules(): array;

    /** @return array<string, string> Keyed by "field.rule". */
    public function messages(): array
    {
        return [];
    }

    /**
     * Normalise raw input (trim, lowercase, ...) before rules run.
     */
    protected function prepareForValidation(array $data): array
    {
        return $data;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function validateResolved(): void
    {
        if (!$this->authorize()) {
            throw new AuthorizationException();
        }

        $this->validated = $this->validator->validate(
            $this->prepareForValidation($this->request->all()),
            $this->rules(),
            $this->messages(),
        );
    }

    public function validated(): array
    {
        return $this->validated;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->validated[$key] ?? $default;
    }

    public function request(): Request
    {
        return $this->request;
    }
}
