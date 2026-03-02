<?php

namespace Tests\Feature\Api;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_employees(): void
    {
        Employee::factory()->usa()->count(3)->create();

        $response = $this->getJson('/api/employees');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'last_name', 'salary', 'country'],
                ],
            ]);
    }

    public function test_can_filter_employees_by_country(): void
    {
        Employee::factory()->usa()->count(2)->create();
        Employee::factory()->germany()->count(3)->create();

        $response = $this->getJson('/api/employees?country=USA');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_create_usa_employee(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
            'country' => 'USA',
            'ssn' => '123-45-6789',
            'address' => '123 Main St, New York, NY',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'John')
            ->assertJsonPath('data.country', 'USA')
            ->assertJsonPath('data.ssn', '123-45-6789');

        $this->assertDatabaseHas('employees', ['name' => 'John', 'country' => 'USA']);
    }

    public function test_can_create_germany_employee(): void
    {
        $data = [
            'name' => 'Hans',
            'last_name' => 'Mueller',
            'salary' => 65000,
            'country' => 'Germany',
            'goal' => 'Increase team productivity by 20%',
            'tax_id' => 'DE123456789',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.country', 'Germany')
            ->assertJsonPath('data.tax_id', 'DE123456789');
    }

    public function test_usa_employee_requires_ssn(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
            'country' => 'USA',
            'address' => '123 Main St',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('ssn');
    }

    public function test_germany_employee_requires_tax_id_format(): void
    {
        $data = [
            'name' => 'Hans',
            'last_name' => 'Mueller',
            'salary' => 65000,
            'country' => 'Germany',
            'goal' => 'Some goal',
            'tax_id' => 'INVALID',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('tax_id');
    }

    public function test_can_update_employee(): void
    {
        $employee = Employee::factory()->usa()->create(['salary' => 50000]);

        $response = $this->putJson("/api/employees/{$employee->id}", [
            'salary' => 75000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.salary', 75000);

        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'salary' => 75000]);
    }

    public function test_can_delete_employee(): void
    {
        $employee = Employee::factory()->usa()->create();

        $response = $this->deleteJson("/api/employees/{$employee->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_salary_must_be_greater_than_zero(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 0,
            'country' => 'USA',
            'ssn' => '123-45-6789',
            'address' => '123 Main St',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('salary');
    }

    public function test_invalid_country_returns_validation_error(): void
    {
        $data = [
            'name' => 'John',
            'last_name' => 'Doe',
            'salary' => 75000,
            'country' => 'France',
        ];

        $response = $this->postJson('/api/employees', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('country');
    }
}
