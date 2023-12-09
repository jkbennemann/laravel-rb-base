<?php

namespace Raidboxes\RbBase\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Please add Rules in the Business-Domain Namespace
 * in Domain\Raidboxes\Rules
 * or if they are specific to a Domain into \Domain\Example\Rules
 */
abstract class BaseValidationRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
    }
}
