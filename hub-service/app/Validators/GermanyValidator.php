<?php

namespace App\Validators;

use App\Contracts\CountryValidatorInterface;
use App\Enums\CountryEnum;

class GermanyValidator implements CountryValidatorInterface
{
    public function validate(array $employee): array
    {
        return [
            $this->validateSalary($employee),
            $this->validateGoal($employee),
            $this->validateTaxId($employee),
        ];
    }

    public function getCountryCode(): string
    {
        return CountryEnum::COUNTRY_GERMANY->value;
    }

    public function getRequiredFields(): array
    {
        return ['salary', 'goal', 'tax_id'];
    }

    private function validateSalary(array $employee): array
    {
        $value = $employee['salary'] ?? 0;
        $isComplete = is_numeric($value) && (float) $value > 0;

        return [
            'field' => 'salary',
            'status' => $isComplete ? 'complete' : 'incomplete',
            'message' => $isComplete ? null : 'Salary is required and must be greater than 0.',
        ];
    }

    private function validateGoal(array $employee): array
    {
        $value = $employee['goal'] ?? null;
        $isComplete = !empty(trim((string) $value));

        return [
            'field' => 'goal',
            'status' => $isComplete ? 'complete' : 'incomplete',
            'message' => $isComplete ? null : 'Goal is required.',
        ];
    }

    private function validateTaxId(array $employee): array
    {
        $value = $employee['tax_id'] ?? null;
        $isComplete = !empty($value) && preg_match('/^DE\d{9}$/', $value);

        return [
            'field' => 'tax_id',
            'status' => $isComplete ? 'complete' : 'incomplete',
            'message' => $isComplete ? null : 'Tax ID is required (format: DE followed by 9 digits).',
        ];
    }
}
