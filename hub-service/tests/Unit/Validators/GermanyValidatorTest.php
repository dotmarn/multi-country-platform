<?php

namespace Tests\Unit\Validators;

use App\Validators\GermanyValidator;
use PHPUnit\Framework\TestCase;

class GermanyValidatorTest extends TestCase
{
    private GermanyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new GermanyValidator();
    }

    public function test_it_returns_germany_country_code(): void
    {
        $this->assertEquals('Germany', $this->validator->getCountryCode());
    }

    public function test_it_returns_correct_required_fields(): void
    {
        $this->assertEquals(['salary', 'goal', 'tax_id'], $this->validator->getRequiredFields());
    }

    public function test_complete_employee_passes_all_validations(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Increase team productivity by 20%',
            'tax_id' => 'DE123456789',
        ];

        $results = $this->validator->validate($employee);

        foreach ($results as $result) {
            $this->assertEquals('complete', $result['status'], "Field {$result['field']} should be complete");
            $this->assertNull($result['message']);
        }
    }

    public function test_zero_salary_fails_validation(): void
    {
        $employee = [
            'salary' => 0,
            'goal' => 'Some goal',
            'tax_id' => 'DE123456789',
        ];

        $results = $this->validator->validate($employee);
        $salaryResult = collect($results)->firstWhere('field', 'salary');

        $this->assertEquals('incomplete', $salaryResult['status']);
    }

    public function test_empty_goal_fails_validation(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => '',
            'tax_id' => 'DE123456789',
        ];

        $results = $this->validator->validate($employee);
        $goalResult = collect($results)->firstWhere('field', 'goal');

        $this->assertEquals('incomplete', $goalResult['status']);
    }

    public function test_invalid_tax_id_format_fails_validation(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Some goal',
            'tax_id' => '123456789',
        ];

        $results = $this->validator->validate($employee);
        $taxIdResult = collect($results)->firstWhere('field', 'tax_id');

        $this->assertEquals('incomplete', $taxIdResult['status']);
    }

    public function test_tax_id_with_wrong_prefix_fails(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Some goal',
            'tax_id' => 'FR123456789',
        ];

        $results = $this->validator->validate($employee);
        $taxIdResult = collect($results)->firstWhere('field', 'tax_id');

        $this->assertEquals('incomplete', $taxIdResult['status']);
    }

    public function test_tax_id_with_insufficient_digits_fails(): void
    {
        $employee = [
            'salary' => 65000,
            'goal' => 'Some goal',
            'tax_id' => 'DE12345',
        ];

        $results = $this->validator->validate($employee);
        $taxIdResult = collect($results)->firstWhere('field', 'tax_id');

        $this->assertEquals('incomplete', $taxIdResult['status']);
    }

    public function test_missing_fields_all_fail(): void
    {
        $employee = [];

        $results = $this->validator->validate($employee);

        foreach ($results as $result) {
            $this->assertEquals('incomplete', $result['status'], "Field {$result['field']} should be incomplete");
            $this->assertNotNull($result['message']);
        }
    }
}
