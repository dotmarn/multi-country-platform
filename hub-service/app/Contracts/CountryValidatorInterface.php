<?php

namespace App\Contracts;

interface CountryValidatorInterface
{
    /**
     * Validate an employee's data completeness.
     *
     * @return array<int, array{field: string, status: string, message: string|null}>
     */
    public function validate(array $employee): array;

    /**
     * Get the country code this validator handles.
     */
    public function getCountryCode(): string;

    /**
     * Get the list of required fields for this country.
     *
     * @return string[]
     */
    public function getRequiredFields(): array;
}
