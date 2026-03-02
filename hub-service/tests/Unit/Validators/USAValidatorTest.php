<?php

namespace Tests\Unit\Validators;

use App\Validators\USAValidator;
use PHPUnit\Framework\TestCase;

class USAValidatorTest extends TestCase
{
    private USAValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new USAValidator();
    }

    public function test_it_returns_usa_country_code(): void
    {
        $this->assertEquals('USA', $this->validator->getCountryCode());
    }

    public function test_it_returns_correct_required_fields(): void
    {
        $this->assertEquals(['ssn', 'salary', 'address'], $this->validator->getRequiredFields());
    }

    public function test_complete_employee_passes_all_validations(): void
    {
        $employee = [
            'ssn' => '123-45-6789',
            'salary' => 75000,
            'address' => '123 Main St, New York, NY',
        ];

        $results = $this->validator->validate($employee);

        foreach ($results as $result) {
            $this->assertEquals('complete', $result['status'], "Field {$result['field']} should be complete");
            $this->assertNull($result['message']);
        }
    }

    public function test_missing_ssn_fails_validation(): void
    {
        $employee = [
            'salary' => 75000,
            'address' => '123 Main St',
        ];

        $results = $this->validator->validate($employee);
        $ssnResult = collect($results)->firstWhere('field', 'ssn');

        $this->assertEquals('incomplete', $ssnResult['status']);
        $this->assertNotNull($ssnResult['message']);
    }

    public function test_invalid_ssn_format_fails_validation(): void
    {
        $employee = [
            'ssn' => '12345678',
            'salary' => 75000,
            'address' => '123 Main St',
        ];

        $results = $this->validator->validate($employee);
        $ssnResult = collect($results)->firstWhere('field', 'ssn');

        $this->assertEquals('incomplete', $ssnResult['status']);
    }

    public function test_zero_salary_fails_validation(): void
    {
        $employee = [
            'ssn' => '123-45-6789',
            'salary' => 0,
            'address' => '123 Main St',
        ];

        $results = $this->validator->validate($employee);
        $salaryResult = collect($results)->firstWhere('field', 'salary');

        $this->assertEquals('incomplete', $salaryResult['status']);
    }

    public function test_empty_address_fails_validation(): void
    {
        $employee = [
            'ssn' => '123-45-6789',
            'salary' => 75000,
            'address' => '',
        ];

        $results = $this->validator->validate($employee);
        $addressResult = collect($results)->firstWhere('field', 'address');

        $this->assertEquals('incomplete', $addressResult['status']);
    }

    public function test_null_address_fails_validation(): void
    {
        $employee = [
            'ssn' => '123-45-6789',
            'salary' => 75000,
            'address' => null,
        ];

        $results = $this->validator->validate($employee);
        $addressResult = collect($results)->firstWhere('field', 'address');

        $this->assertEquals('incomplete', $addressResult['status']);
    }
}
