<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Validator;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function test_returns_only_validated_fields(): void
    {
        $data = (new Validator())->validate(
            ['username' => 'rocky', 'role' => 'admin'],
            ['username' => 'required|alpha_num|min:3'],
        );

        $this->assertSame(['username' => 'rocky'], $data);
    }

    public function test_collects_first_error_per_field(): void
    {
        try {
            (new Validator())->validate(
                ['email' => 'nope', 'amount' => '5'],
                ['email' => 'required|email', 'amount' => 'required|integer|min:10', 'name' => 'required'],
            );
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['email', 'amount', 'name'], array_keys($e->errors()));
            $this->assertCount(1, $e->errors()['amount']);
        }
    }

    public function test_optional_empty_fields_skip_rules(): void
    {
        $data = (new Validator())->validate(['bio' => ''], ['bio' => 'string|min:10']);

        $this->assertSame(['bio' => ''], $data);
    }

    public function test_confirmed(): void
    {
        $this->expectException(ValidationException::class);

        (new Validator())->validate(
            ['password' => 'secret123', 'password_confirmation' => 'other'],
            ['password' => 'required|confirmed'],
        );
    }
}
