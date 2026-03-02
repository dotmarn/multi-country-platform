<?php

namespace Tests\Unit\EventHandlers;

use App\Enums\EventTypesEnum;
use App\EventHandlers\EmployeeDeletedHandler;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeListUpdated;
use App\Services\EmployeeCacheService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EmployeeDeletedHandlerTest extends TestCase
{
    private EmployeeCacheService $cacheService;
    private EmployeeDeletedHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheService = $this->mock(EmployeeCacheService::class);
        $this->handler = new EmployeeDeletedHandler($this->cacheService);
    }

    public function test_it_returns_correct_event_type(): void
    {
        $this->assertEquals(
            EventTypesEnum::EMPLOYEE_DELETED->value,
            $this->handler->getEventType()
        );
    }

    public function test_it_invalidates_cache_for_deleted_employee(): void
    {
        Event::fake();

        $this->cacheService
            ->shouldReceive('invalidateForDeleted')
            ->once()
            ->with(1, 'Germany');

        $this->handler->handle($this->makeEventData());
    }

    public function test_it_does_not_call_put_employee(): void
    {
        Event::fake();

        $this->cacheService->shouldNotReceive('putEmployee');
        $this->cacheService->shouldReceive('invalidateForDeleted')->once();

        $this->handler->handle($this->makeEventData());
    }

    public function test_it_broadcasts_employee_list_updated_and_checklist_updated(): void
    {
        Event::fake([EmployeeListUpdated::class, ChecklistUpdated::class]);

        $this->cacheService->shouldReceive('invalidateForDeleted')->once();

        $this->handler->handle($this->makeEventData());

        Event::assertDispatched(EmployeeListUpdated::class, function ($event) {
            return $event->country === 'Germany' && $event->action === 'deleted';
        });

        Event::assertDispatched(ChecklistUpdated::class, function ($event) {
            return $event->country === 'Germany';
        });
    }

    private function makeEventData(): array
    {
        return [
            'event_type' => 'EmployeeDeleted',
            'event_id' => 'test-uuid-789',
            'timestamp' => '2024-02-09T10:30:00Z',
            'country' => 'Germany',
            'data' => [
                'employee_id' => 1,
                'changed_fields' => [],
                'employee' => [
                    'id' => 1,
                    'name' => 'Hans',
                    'last_name' => 'Mueller',
                    'salary' => 65000,
                    'goal' => 'Increase productivity',
                    'tax_id' => 'DE123456789',
                    'country' => 'Germany',
                ],
            ],
        ];
    }
}
