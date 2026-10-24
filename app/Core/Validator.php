<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\ValidationException;

final class Validator
{
    /**
     * @param array<string, string|list<string>> $rules e.g. ['email' => 'required|email|max:255']
     * @return array<string, mixed> Only the fields that have rules.
     * @throws ValidationException
     */
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_string($fieldRules) ? explode('|', $fieldRules) : $fieldRules;
            $value = $data[$field] ?? null;

            if (!in_array('required', $fieldRules, true) && ($value === null || $value === '')) {
                if (array_key_exists($field, $data)) {
                    $validated[$field] = $value;
                }
                continue;
            }

            foreach ($fieldRules as $rule) {
                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $error = $this->check($field, $value, $name, $parameter, $data);

                if ($error !== null) {
                    $errors[$field][] = $messages["{$field}.{$name}"] ?? $error;
                    break;
                }
            }

            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $validated;
    }

    private function check(string $field, mixed $value, string $rule, ?string $parameter, array $data): ?string
    {
        $label = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => $value === null || $value === '' || $value === []
                ? "The {$label} field is required." : null,
            'string' => is_string($value) ? null : "The {$label} must be text.",
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                ? null : "The {$label} must be a valid email address.",
            'numeric' => is_numeric($value) ? null : "The {$label} must be a number.",
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false
                ? null : "The {$label} must be a whole number.",
            'boolean' => in_array($value, [true, false, 0, 1, '0', '1', 'on', 'off'], true)
                ? null : "The {$label} must be true or false.",
            'alpha_num' => is_string($value) && ctype_alnum($value)
                ? null : "The {$label} may only contain letters and numbers.",
            'min' => $this->size($value) >= (float) $parameter
                ? null : "The {$label} must be at least {$parameter}" . (is_numeric($value) ? '.' : ' characters.'),
            'max' => $this->size($value) <= (float) $parameter
                ? null : "The {$label} may not be greater than {$parameter}" . (is_numeric($value) ? '.' : ' characters.'),
            'in' => in_array((string) $value, explode(',', (string) $parameter), true)
                ? null : "The selected {$label} is invalid.",
            'regex' => is_string($value) && preg_match((string) $parameter, $value) === 1
                ? null : "The {$label} format is invalid.",
            'confirmed' => ($data["{$field}_confirmation"] ?? null) === $value
                ? null : "The {$label} confirmation does not match.",
            default => throw new \InvalidArgumentException("Unknown validation rule [{$rule}]."),
        };
    }

    private function size(mixed $value): float
    {
        return match (true) {
            is_numeric($value) => (float) $value,
            is_array($value) => count($value),
            default => mb_strlen((string) $value),
        };
    }
}
