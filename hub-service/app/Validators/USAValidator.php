<?php

namespace App\Validators;

use App\Contracts\CountryValidatorInterface;
use App\Enums\CountryEnum;

class USAValidator implements CountryValidatorInterface
{
    public function validate(array $employee): array
    {
        return [
            $this->validateSsn($employee),
            $this->validateSalary($employee),
            $this->validateAddress($employee),
        ];
    }

    public function getCountryCode(): string
    {
        return CountryEnum::COUNTRY_USA->value;
    }

    public function getRequiredFields(): array
    {
        return ['ssn', 'salary', 'address'];
    }

    private function validateSsn(array $employee): array
    {
        $value = $employee['ssn'] ?? null;
        $isComplete = !empty($value) && preg_match('/^\d{3}-\d{2}-\d{4}$/', $value);

        return [
            'field' => 'ssn',
            'status' => $isComplete ? 'complete' : 'incomplete',
            'message' => $isComplete ? null : 'SSN is required (format: XXX-XX-XXXX).',
        ];
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

    private function validateAddress(array $employee): array
    {
        $value = $employee['address'] ?? null;
        $isComplete = !empty(trim((string) $value));

        return [
            'field' => 'address',
            'status' => $isComplete ? 'complete' : 'incomplete',
            'message' => $isComplete ? null : 'Address is required.',
        ];
    }
}
