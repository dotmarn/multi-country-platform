<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\RabbitMQService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly RabbitMQService $rabbitMQService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Employee::query()->when($request->has('country'), function ($q) use ($request) {
            $q->where('country', $request->input('country'));
        });

        $perPage = $request->input('per_page', 15);
        $employees = $query->paginate($perPage);

        return EmployeeResource::collection($employees);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());

        $this->publishEvent('EmployeeCreated', $employee);

        return (new EmployeeResource($employee))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $employee->update($request->validated());

        $changedFields = $employee->getChangedFields();

        $this->publishEvent('EmployeeUpdated', $employee, $changedFields);

        return new EmployeeResource($employee);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employeeData = $employee->toArray();

        $employee->delete();

        $this->publishEvent('EmployeeDeleted', null, [], $employeeData);

        return response()->json(['message' => 'Employee deleted successfully.'], 200);
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

            $country = strtolower($payload['country']);
            $eventAction = strtolower(str_replace('Employee', '', $eventType));
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
