<?php

namespace App\EventHandlers;

use App\Contracts\EventProcessorInterface;
use App\Enums\EventTypesEnum;
use App\Events\ChecklistUpdated;
use App\Events\EmployeeDataChanged;
use App\Events\EmployeeListUpdated;
use App\Services\EmployeeCacheService;
use Illuminate\Support\Facades\Log;

class EmployeeUpdatedHandler implements EventProcessorInterface
{
    public function __construct(
        private readonly EmployeeCacheService $cacheService,
    ) {}

    public function handle(array $eventData): void
    {
        $country = $eventData['country'];
        $employeeId = $eventData['data']['employee_id'];
        $changedFields = $eventData['data']['changed_fields'] ?? [];
        $employeeData = $eventData['data']['employee'] ?? [];

        Log::info('Processing EmployeeUpdated event', [
            'event_id' => $eventData['event_id'],
            'employee_id' => $employeeId,
            'country' => $country,
            'changed_fields' => $changedFields,
        ]);

        if ($employeeId && !empty($employeeData)) {
            $this->cacheService->putEmployee($employeeId, $employeeData);
        }

        $this->cacheService->invalidateForUpdated($employeeId, $country);

        broadcast(new EmployeeListUpdated($country, 'updated', $employeeData));
        broadcast(new EmployeeDataChanged($employeeId, $changedFields, $employeeData));
        broadcast(new ChecklistUpdated($country));

        Log::info('EmployeeUpdated event processed successfully', [
            'employee_id' => $employeeId,
        ]);
    }

    public function getEventType(): string
    {
        return EventTypesEnum::EMPLOYEE_UPDATED->value;
    }
}
