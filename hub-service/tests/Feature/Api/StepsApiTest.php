<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class StepsApiTest extends TestCase
{
    public function test_usa_returns_dashboard_and_employees_steps(): void
    {
        $response = $this->getJson('/api/steps?country=USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'country',
                    'steps' => [
                        '*' => ['id', 'label', 'icon', 'path', 'order', 'is_active'],
                    ],
                ],
            ])
            ->assertJsonPath('data.country', 'USA')
            ->assertJsonCount(2, 'data.steps');

        $stepIds = collect($response->json('data.steps'))->pluck('id')->toArray();
        $this->assertEquals(['dashboard', 'employees'], $stepIds);
    }

    public function test_germany_returns_three_steps_including_documentation(): void
    {
        $response = $this->getJson('/api/steps?country=Germany');

        $response->assertStatus(200)
            ->assertJsonPath('data.country', 'Germany')
            ->assertJsonCount(3, 'data.steps');

        $stepIds = collect($response->json('data.steps'))->pluck('id')->toArray();
        $this->assertEquals(['dashboard', 'employees', 'documentation'], $stepIds);
    }

    public function test_missing_country_returns_validation_error(): void
    {
        $response = $this->getJson('/api/steps');

        $response->assertStatus(422);
    }

    public function test_invalid_country_returns_validation_error(): void
    {
        $response = $this->getJson('/api/steps?country=France');

        $response->assertStatus(422);
    }
}
