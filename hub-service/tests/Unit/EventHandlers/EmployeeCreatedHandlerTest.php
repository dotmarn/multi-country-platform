<?php

namespace Tests\Unit\EventHandlers;

use App\Enums\EventTypesEnum;
use App\EventHandlers\EmployeeCreatedHandler;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeListUpdated;
use App\Services\EmployeeCacheService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmployeeCreatedHandlerTest extends TestCase
{
    private EmployeeCacheService $cacheService;
    private EmployeeCreatedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = $this->mock(EmployeeCacheService::class);
        $this->handler = new EmployeeCreatedHandler($this->cacheService);
    }

    public function test_it_returns_correct_event_type(): void
    {
        $this->assertEquals(
            EventTypesEnum::EMPLOYEE_CREATED->value,
            $this->handler->getEventType()
        );
    }

    public function test_it_caches_employee_and_invalidates_country_cache(): void
    {
        Event::fake();

        $eventData = $this->makeEventData();

        $this->cacheService
            ->shouldReceive('putEmployee')
            ->once()
            ->with(1, $eventData['data']['employee']);

        $this->cacheService
            ->shouldReceive('invalidateForCreated')
            ->once()
            ->with('USA');

        $this->handler->handle($eventData);
    }

    public function test_it_broadcasts_employee_list_updated_and_checklist_updated(): void
    {
        Event::fake([EmployeeListUpdated::class, ChecklistUpdated::class]);

        $this->cacheService->shouldReceive('putEmployee')->once();
        $this->cacheService->shouldReceive('invalidateForCreated')->once();

        $this->handler->handle($this->makeEventData());

        Event::assertDispatched(EmployeeListUpdated::class, function ($event) {
            return $event->country === 'USA' && $event->action === 'created';
        });

        Event::assertDispatched(ChecklistUpdated::class, function ($event) {
            return $event->country === 'USA';
        });
    }

    public function test_it_skips_cache_put_when_employee_data_is_empty(): void
    {
        Event::fake();

        $eventData = $this->makeEventData();
        $eventData['data']['employee'] = [];

        $this->cacheService->shouldNotReceive('putEmployee');
        $this->cacheService->shouldReceive('invalidateForCreated')->once()->with('USA');

        $this->handler->handle($eventData);
    }

    private function makeEventData(): array
    {
        return [
            'event_type' => 'EmployeeCreated',
            'event_id' => 'test-uuid-123',
            'timestamp' => '2024-02-09T10:30:00Z',
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'changed_fields' => [],
                'employee' => [
                    'id' => 1,
                    'name' => 'John',
                    'last_name' => 'Doe',
                    'salary' => 75000,
                    'ssn' => '123-45-6789',
                    'address' => '123 Main St, New York, NY',
                    'country' => 'USA',
                ],
            ],
        ];
    }
}
