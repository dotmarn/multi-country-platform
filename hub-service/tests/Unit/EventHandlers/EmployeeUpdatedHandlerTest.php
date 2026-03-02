<?php

namespace Tests\Unit\EventHandlers;

use App\Enums\EventTypesEnum;
use App\EventHandlers\EmployeeUpdatedHandler;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeDataChanged;
use App\Events\EmployeeListUpdated;
use App\Services\EmployeeCacheService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmployeeUpdatedHandlerTest extends TestCase
{
    private EmployeeCacheService $cacheService;
    private EmployeeUpdatedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = $this->mock(EmployeeCacheService::class);
        $this->handler = new EmployeeUpdatedHandler($this->cacheService);
    }

    public function test_it_returns_correct_event_type(): void
    {
        $this->assertEquals(
            EventTypesEnum::EMPLOYEE_UPDATED->value,
            $this->handler->getEventType()
        );
    }

    public function test_it_updates_cache_and_invalidates_country_cache(): void
    {
        Event::fake();

        $eventData = $this->makeEventData();

        $this->cacheService
            ->shouldReceive('putEmployee')
            ->once()
            ->with(1, $eventData['data']['employee']);

        $this->cacheService
            ->shouldReceive('invalidateForUpdated')
            ->once()
            ->with(1, 'USA');

        $this->handler->handle($eventData);
    }

    public function test_it_broadcasts_all_three_events(): void
    {
        Event::fake([EmployeeListUpdated::class, EmployeeDataChanged::class, ChecklistUpdated::class]);

        $this->cacheService->shouldReceive('putEmployee')->once();
        $this->cacheService->shouldReceive('invalidateForUpdated')->once();

        $this->handler->handle($this->makeEventData());

        Event::assertDispatched(EmployeeListUpdated::class, function ($event) {
            return $event->country === 'USA' && $event->action === 'updated';
        });

        Event::assertDispatched(EmployeeDataChanged::class, function ($event) {
            return $event->employeeId === 1
                && $event->changedFields === ['salary'];
        });

        Event::assertDispatched(ChecklistUpdated::class, function ($event) {
            return $event->country === 'USA';
        });
    }

    public function test_it_handles_empty_changed_fields(): void
    {
        Event::fake();

        $eventData = $this->makeEventData();
        $eventData['data']['changed_fields'] = [];

        $this->cacheService->shouldReceive('putEmployee')->once();
        $this->cacheService->shouldReceive('invalidateForUpdated')->once();

        $this->handler->handle($eventData);

        Event::assertDispatched(EmployeeDataChanged::class, function ($event) {
            return $event->changedFields === [];
        });
    }

    public function test_it_skips_cache_put_when_employee_data_is_empty(): void
    {
        Event::fake();

        $eventData = $this->makeEventData();
        $eventData['data']['employee'] = [];

        $this->cacheService->shouldNotReceive('putEmployee');
        $this->cacheService->shouldReceive('invalidateForUpdated')->once()->with(1, 'USA');

        $this->handler->handle($eventData);
    }

    private function makeEventData(): array
    {
        return [
            'event_type' => 'EmployeeUpdated',
            'event_id' => 'test-uuid-456',
            'timestamp' => '2024-02-09T10:30:00Z',
            'country' => 'USA',
            'data' => [
                'employee_id' => 1,
                'changed_fields' => ['salary'],
                'employee' => [
                    'id' => 1,
                    'name' => 'John',
                    'last_name' => 'Doe',
                    'salary' => 80000,
                    'ssn' => '123-45-6789',
                    'address' => '123 Main St, New York, NY',
                    'country' => 'USA',
                ],
            ],
        ];
    }
}
