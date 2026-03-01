<?php

namespace App\Services;

use App\Enums\StatusEnum;
use App\Validators\CountryValidatorFactory;
use Illuminate\Support\Facades\Log;

class ChecklistService
{
    public function __construct(
        private readonly EmployeeCacheService $cacheService,
        private readonly CountryValidatorFactory $validatorFactory,
    ) {}

    /**
     * Get the full checklist data for a country.
     */
    public function getChecklist(string $country): array
    {
        $cached = $this->cacheService->getChecklist($country);

        if ($cached !== null) {
            Log::debug('Checklist cache hit', ['country' => $country]);
            return $cached;
        }

        $checklist = $this->computeChecklist($country);

        $this->cacheService->putChecklist($country, $checklist);

        return $checklist;
    }

    /**
     * Compute checklist data from scratch.
     */
    public function computeChecklist(string $country): array
    {
        $validator = $this->validatorFactory->make($country);
        $employees = $this->cacheService->getAllEmployees($country);

        $employeeChecklists = [];
        $completeCount = 0;

        foreach ($employees as $employee) {
            $fields = $validator->validate($employee);
            $totalFields = count($fields);
            $completedFields = count(array_filter($fields, fn ($f) => $f['status'] === StatusEnum::STATUS_COMPLETE->value));
            $completionPercentage = $totalFields > 0 ? round(($completedFields / $totalFields) * 100, 1) : 0;
            $isComplete = $completedFields === $totalFields;

            if ($isComplete) {
                $completeCount++;
            }

            $employeeChecklists[] = [
                'employee_id' => $employee['id'],
                'name' => ($employee['name'] ?? '') . ' ' . ($employee['last_name'] ?? ''),
                'is_complete' => $isComplete,
                'completion_percentage' => $completionPercentage,
                'fields' => $fields,
            ];
        }

        $totalEmployees = count($employees);
        $overallCompletion = $totalEmployees > 0
            ? round(($completeCount / $totalEmployees) * 100, 1)
            : 0;

        return [
            'country' => $country,
            'overall_completion' => $overallCompletion,
            'total_employees' => $totalEmployees,
            'complete_count' => $completeCount,
            'incomplete_count' => $totalEmployees - $completeCount,
            'employees' => $employeeChecklists,
        ];
    }
}
