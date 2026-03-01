<?php

namespace App\EventHandlers;

use App\Contracts\EventProcessorInterface;
use App\Enums\EventTypesEnum;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeListUpdated;
use App\Services\EmployeeCacheService;
use Illuminate\Support\Facades\Log;

class EmployeeCreatedHandler implements EventProcessorInterface
{
    public function __construct(
        private readonly EmployeeCacheService $cacheService,
    ) {}

    public function handle(array $eventData): void
    {
        $country = $eventData['country'];
        $employeeId = $eventData['data']['employee_id'];
        $employeeData = $eventData['data']['employee'] ?? [];

        Log::info('Processing Employee Created event', [
            'event_id' => $eventData['event_id'],
            'employee_id' => $employeeId,
            'country' => $country,
        ]);

        if ($employeeId && !empty($employeeData)) {
            $this->cacheService->putEmployee($employeeId, $employeeData);
        }

        $this->cacheService->invalidateForCreated($country);

        broadcast(new EmployeeListUpdated($country, 'created', $employeeData));
        broadcast(new ChecklistUpdated($country));

        Log::info('Employee Created event processed successfully', [
            'employee_id' => $employeeId,
        ]);
    }

    public function getEventType(): string
    {
        return EventTypesEnum::EMPLOYEE_CREATED->value;
    }
}
