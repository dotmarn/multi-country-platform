<?php

namespace Tests\Unit\Services;

use App\Services\ChecklistService;
use App\Services\EmployeeCacheService;
use App\Validators\CountryValidatorFactory;
use Tests\TestCase;

class ChecklistServiceTest extends TestCase
{
    private EmployeeCacheService $cacheService;
    private ChecklistService $checklistService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = $this->mock(EmployeeCacheService::class);
        $this->checklistService = new ChecklistService(
            $this->cacheService,
            new CountryValidatorFactory(),
        );
    }

    public function test_it_returns_cached_checklist_when_available(): void
    {
        $cached = ['country' => 'USA', 'overall_completion' => 100.0];

        $this->cacheService
            ->shouldReceive('getChecklist')
            ->once()
            ->with('USA')
            ->andReturn($cached);

        $this->cacheService->shouldNotReceive('getAllEmployees');
        $this->cacheService->shouldNotReceive('putChecklist');

        $result = $this->checklistService->getChecklist('USA');

        $this->assertEquals($cached, $result);
    }

    public function test_it_computes_and_caches_checklist_on_cache_miss(): void
    {
        $this->cacheService
            ->shouldReceive('getChecklist')
            ->once()
            ->with('USA')
            ->andReturn(null);

        $this->cacheService
            ->shouldReceive('getAllEmployees')
            ->once()
            ->with('USA')
            ->andReturn([
                [
                    'id' => 1,
                    'name' => 'John',
                    'last_name' => 'Doe',
                    'salary' => 75000,
                    'ssn' => '123-45-6789',
                    'address' => '123 Main St',
                    'country' => 'USA',
                ],
            ]);

        $this->cacheService
            ->shouldReceive('putChecklist')
            ->once()
            ->withArgs(function ($country, $data) {
                return $country === 'USA' && $data['total_employees'] === 1;
            });

        $result = $this->checklistService->getChecklist('USA');

        $this->assertEquals('USA', $result['country']);
        $this->assertEquals(1, $result['total_employees']);
    }

    public function test_compute_checklist_for_fully_complete_usa_employees(): void
    {
        $this->cacheService
            ->shouldReceive('getAllEmployees')
            ->with('USA')
            ->andReturn([
                [
                    'id' => 1, 'name' => 'John', 'last_name' => 'Doe',
                    'salary' => 75000, 'ssn' => '123-45-6789', 'address' => '123 Main St',
                ],
                [
                    'id' => 2, 'name' => 'Jane', 'last_name' => 'Smith',
                    'salary' => 80000, 'ssn' => '987-65-4321', 'address' => '456 Oak Ave',
                ],
            ]);

        $result = $this->checklistService->computeChecklist('USA');

        $this->assertEquals(100.0, $result['overall_completion']);
        $this->assertEquals(2, $result['total_employees']);
        $this->assertEquals(2, $result['complete_count']);
        $this->assertEquals(0, $result['incomplete_count']);

        foreach ($result['employees'] as $emp) {
            $this->assertTrue($emp['is_complete']);
            $this->assertEquals(100.0, $emp['completion_percentage']);
        }
    }

    public function test_compute_checklist_for_partially_complete_usa_employees(): void
    {
        $this->cacheService
            ->shouldReceive('getAllEmployees')
            ->with('USA')
            ->andReturn([
                [
                    'id' => 1, 'name' => 'John', 'last_name' => 'Doe',
                    'salary' => 75000, 'ssn' => '123-45-6789', 'address' => '123 Main St',
                ],
                [
                    'id' => 2, 'name' => 'Missing', 'last_name' => 'Data',
                    'salary' => 0, 'ssn' => null, 'address' => '',
                ],
            ]);

        $result = $this->checklistService->computeChecklist('USA');

        $this->assertEquals(50.0, $result['overall_completion']);
        $this->assertEquals(1, $result['complete_count']);
        $this->assertEquals(1, $result['incomplete_count']);

        $incomplete = collect($result['employees'])->firstWhere('employee_id', 2);
        $this->assertFalse($incomplete['is_complete']);
        $this->assertEquals(0.0, $incomplete['completion_percentage']);
    }

    public function test_compute_checklist_for_germany_employees(): void
    {
        $this->cacheService
            ->shouldReceive('getAllEmployees')
            ->with('Germany')
            ->andReturn([
                [
                    'id' => 1, 'name' => 'Hans', 'last_name' => 'Mueller',
                    'salary' => 65000, 'goal' => 'Be productive', 'tax_id' => 'DE123456789',
                ],
                [
                    'id' => 2, 'name' => 'Fritz', 'last_name' => 'Weber',
                    'salary' => 50000, 'goal' => '', 'tax_id' => 'DE987654321',
                ],
            ]);

        $result = $this->checklistService->computeChecklist('Germany');

        $this->assertEquals('Germany', $result['country']);
        $this->assertEquals(2, $result['total_employees']);
        $this->assertEquals(1, $result['complete_count']);
        $this->assertEquals(1, $result['incomplete_count']);
        $this->assertEquals(50.0, $result['overall_completion']);

        // Fritz has an empty goal, so 2 of 3 fields are complete
        $fritz = collect($result['employees'])->firstWhere('employee_id', 2);
        $this->assertFalse($fritz['is_complete']);
        $this->assertEquals(66.7, $fritz['completion_percentage']);
    }

    public function test_compute_checklist_for_empty_employee_list(): void
    {
        $this->cacheService
            ->shouldReceive('getAllEmployees')
            ->with('USA')
            ->andReturn([]);

        $result = $this->checklistService->computeChecklist('USA');

        $this->assertEquals(0, $result['total_employees']);
        $this->assertEquals(0.0, $result['overall_completion']);
        $this->assertEmpty($result['employees']);
    }

    public function test_employee_names_are_concatenated(): void
    {
        $this->cacheService
            ->shouldReceive('getAllEmployees')
            ->with('USA')
            ->andReturn([
                [
                    'id' => 1, 'name' => 'John', 'last_name' => 'Doe',
                    'salary' => 75000, 'ssn' => '123-45-6789', 'address' => 'Some St',
                ],
            ]);

        $result = $this->checklistService->computeChecklist('USA');

        $this->assertEquals('John Doe', $result['employees'][0]['name']);
    }
}
