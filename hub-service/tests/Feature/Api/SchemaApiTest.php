<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class SchemaApiTest extends TestCase
{
    public function test_usa_dashboard_returns_three_widgets(): void
    {
        $response = $this->getJson('/api/schema/dashboard?country=USA');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'country',
                'step_id',
                'schema' => [
                    'type',
                    'widgets' => [
                        '*' => ['id', 'type', 'title', 'data_source', 'refresh_channel'],
                    ],
                ],
            ])
            ->assertJsonPath('step_id', 'dashboard')
            ->assertJsonPath('schema.type', 'dashboard')
            ->assertJsonCount(3, 'schema.widgets');

        $widgetIds = collect($response->json('schema.widgets'))->pluck('id')->toArray();
        $this->assertContains('employee_count', $widgetIds);
        $this->assertContains('average_salary', $widgetIds);
        $this->assertContains('completion_rate', $widgetIds);
    }

    public function test_germany_dashboard_returns_two_widgets(): void
    {
        $response = $this->getJson('/api/schema/dashboard?country=Germany');

        $response->assertStatus(200)
            ->assertJsonPath('schema.type', 'dashboard')
            ->assertJsonCount(2, 'schema.widgets');

        $widgetIds = collect($response->json('schema.widgets'))->pluck('id')->toArray();
        $this->assertContains('employee_count', $widgetIds);
        $this->assertContains('goal_tracking', $widgetIds);
    }

    public function test_nonexistent_step_returns_404(): void
    {
        $response = $this->getJson('/api/schema/nonexistent?country=USA');

        $response->assertStatus(404);
    }

    public function test_documentation_step_only_available_for_germany(): void
    {
        $response = $this->getJson('/api/schema/documentation?country=Germany');
        $response->assertStatus(200);

        $response = $this->getJson('/api/schema/documentation?country=USA');
        $response->assertStatus(404);
    }
}
