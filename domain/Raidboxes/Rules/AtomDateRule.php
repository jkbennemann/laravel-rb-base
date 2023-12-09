<?php

declare(strict_types=1);

namespace Raidboxes\Domain\Raidboxes\Rules;

use Raidboxes\RbBase\Rules\BaseValidationRule;
use Closure;
use DateTime;
use DateTimeInterface;

class AtomDateRule extends BaseValidationRule
{
    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //phpcs:ignore PSR2.ControlStructures.ControlStructureSpacing.SpacingAfterOpenBrace
        if (
            !(new DateTime())->createFromFormat('!Y-m-d\TH:i:s.vp', $value)
            && !(new DateTime())->createFromFormat('!' . DateTimeInterface::ATOM, $value)
        ) {
            $fail('date.raidboxes.error|default:The :attribute field must match the format \'Y-m-dTH:i:s.vp\'');
        }
    }
}
