<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\RabbitMQService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly RabbitMQService $rabbitMQService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Employee::query()->when($request->has('country'), function ($q) use ($request) {
            $q->where('country', $request->input('country'));
        });

        $perPage = $request->input('per_page', 15);
        $employees = $query->paginate($perPage);

        return response()->success(
            Response::HTTP_OK,
            'Employees fetched successfully',
            EmployeeResource::collection($employees)
        );
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        $this->publishEvent(EventTypeEnum::EMPLOYEE_CREATED->value, $employee);

        return response()->success(
            Response::HTTP_CREATED,
            'Employee created successfully',
            new EmployeeResource($employee)
        );
    }

    public function show(Employee $employee): JsonResponse
    {
        return response()->success(
            Response::HTTP_OK,
            'Employee fetched successfully',
            new EmployeeResource($employee)
        );
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());

        $changedFields = $employee->getChangedFields();

        $this->publishEvent(EventTypeEnum::EMPLOYEE_UPDATED->value, $employee, $changedFields);

        return response()->success(
            Response::HTTP_OK,
            'Employee updated successfully',
            new EmployeeResource($employee)
        );
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employeeData = $employee->toArray();

        $employee->delete();

        $this->publishEvent(EventTypeEnum::EMPLOYEE_DELETED->value, null, [], $employeeData);

        return response()->success(
            Response::HTTP_OK,
            'Employee deleted successfully'
        );
    }

    private function publishEvent(string $eventType, ?Employee $employee, array $changedFields = [], array $deletedData = []): void
    {
        try {
            $payload = [
                'event_type' => $eventType,
                'event_id' => (string) Uuid::uuid4(),
                'timestamp' => now()->toIso8601String(),
                'country' => $employee?->country ?? $deletedData['country'] ?? 'unknown',
                'data' => [
                    'employee_id' => $employee?->id ?? $deletedData['id'] ?? null,
                    'changed_fields' => $changedFields,
                    'employee' => $employee
                        ? (new EmployeeResource($employee))->resolve()
                        : $deletedData,
                ],
            ];

            $country = Str::lower($payload['country']);
            $eventAction = Str::lower(Str::replace('Employee', '', $eventType));
            $routingKey = "employee.{$eventAction}.{$country}";

            $this->rabbitMQService->publish(
                exchange: 'employee_events',
                routingKey: $routingKey,
                message: $payload
            );

            Log::info("Published {$eventType} event", [
                'event_id' => $payload['event_id'],
                'employee_id' => $payload['data']['employee_id'],
                'routing_key' => $routingKey,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to publish {$eventType} event", [
                'error' => $e->getMessage(),
                'employee_id' => $employee?->id ?? $deletedData['id'] ?? null,
            ]);
        }
    }
}
