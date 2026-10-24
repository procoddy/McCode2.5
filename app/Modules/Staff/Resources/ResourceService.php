<?php

declare(strict_types=1);

namespace App\Modules\Staff\Resources;

use App\Core\Validator;
use App\Exceptions\ValidationException;
use App\Modules\Inventory\Models\ItemEffect;
use App\Support\Formula;
use InvalidArgumentException;

/**
 * Validates and saves staff resource forms according to their field definitions.
 */
final class ResourceService
{
    public function __construct(private readonly ResourceRepository $repository, private readonly Validator $validator)
    {
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, list<int>>} column values, pivot selections
     * @throws ValidationException
     */
    public function validate(ResourceDefinition $def, array $input, ?int $id = null): array
    {
        $rules = [];
        $data = [];

        foreach ($def->columnFields() as $name => $field) {
            $value = $input[$name] ?? null;
            $value = is_string($value) ? trim($value) : $value;

            [$data[$name], $rules[$name]] = match ($field['type']) {
                'bool' => [empty($value) ? '0' : '1', 'boolean'],
                'select' => [$value === '' ? null : $value, ($field['nullable'] ?? false) ? 'integer' : 'required|integer'],
                'formula' => [$value, 'required|string|max:255'],
                'effects' => [null, 'string'],
                'user' => [$value === '' ? null : $value, 'integer|min:1'],
                'choice' => [$value, 'required|in:' . implode(',', array_keys($field['options']))],
                default => [$value ?? (in_array($field['type'], ['string', 'text'], true) ? '' : null), $field['rules'] ?? 'string'],
            };
        }

        $values = $this->validator->validate($data, $rules);
        $errors = [];

        foreach ($def->columnFields() as $name => $field) {
            $value = $values[$name] ?? null;

            if ($field['type'] === 'select' && $value !== null && !isset($this->repository->options($field['source'])[(int) $value])) {
                $errors[$name] = ['Choose an option from the list.'];
            }

            if ($field['type'] === 'user' && $value !== null) {
                $values[$name] = (int) $value;
                if (!$this->repository->usedBy('users', 'id', (int) $value)) {
                    $errors[$name] = ['No player has that ID.'];
                }
            }

            if ($field['type'] === 'effects') {
                $values[$name] = $this->effects($input[$name] ?? [], $field['max'] ?? 3, $errors, $name);
            }

            if ($field['type'] === 'formula') {
                try {
                    Formula::evaluate((string) $value, ['LEVEL' => 1, 'CRIMEXP' => 1, 'EXP' => 1, 'WILL' => 1, 'IQ' => 1]);
                } catch (InvalidArgumentException $e) {
                    $errors[$name] = [$e->getMessage()];
                }
            }

            if (!empty($field['unique']) && $value !== null && $this->repository->valueTaken($def, $name, $value, $id)) {
                $errors[$name] = ["Another {$def->singular} already uses this value."];
            }

            if (in_array($field['type'], ['int', 'money'], true) && $value !== null) {
                $values[$name] = (int) $value;
            }
        }

        $pivots = [];
        foreach ($def->pivotFields() as $name => $field) {
            $allowed = $this->repository->options($field['source']);
            $pivots[$name] = array_values(array_filter(
                array_map('intval', (array) ($input[$name] ?? [])),
                fn (int $optionId) => isset($allowed[$optionId]),
            ));
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [$values, $pivots];
    }

    /**
     * Rows of [stat, direction, type, amount] from the effects editor, as the
     * JSON stored in items.effects. Rows with no stat are switched off.
     */
    private function effects(mixed $rows, int $max, array &$errors, string $name): string
    {
        $effects = [];

        foreach (array_slice(is_array($rows) ? array_values($rows) : [], 0, $max) as $row) {
            $stat = is_array($row) ? (string) ($row['stat'] ?? '') : '';
            if ($stat === '') {
                continue;
            }

            $amount = $row['amount'] ?? '';
            if (!isset(ItemEffect::STATS[$stat]) || filter_var($amount, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                $errors[$name] = ['Each effect needs a stat from the list and a whole-number amount of at least 1.'];
                continue;
            }

            $effects[] = (new ItemEffect($stat, ($row['direction'] ?? '') !== 'decrease', ($row['type'] ?? '') === 'percent', (int) $amount))->toArray();
        }

        return json_encode($effects, JSON_THROW_ON_ERROR);
    }

    /** Why this row cannot be deleted, or null. */
    public function deleteBlockedReason(ResourceDefinition $def, int $id): ?string
    {
        if (in_array($id, $def->protectedIds, true)) {
            return "This {$def->singular} is required by the game and cannot be deleted.";
        }

        foreach ($def->blockDeleteIf as [$table, $column, $message]) {
            if ($this->repository->usedBy($table, $column, $id)) {
                return $message;
            }
        }

        return null;
    }
}
