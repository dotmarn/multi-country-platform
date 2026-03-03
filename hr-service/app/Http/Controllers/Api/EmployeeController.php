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

        $this->rabbitMQService->publishEvent(EventTypeEnum::EMPLOYEE_CREATED->value, $employee);

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

        $this->rabbitMQService->publishEvent(EventTypeEnum::EMPLOYEE_UPDATED->value, $employee, $changedFields);

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

        $this->rabbitMQService->publishEvent(EventTypeEnum::EMPLOYEE_DELETED->value, null, [], $employeeData);

        return response()->success(
            Response::HTTP_OK,
            'Employee deleted successfully'
        );
    }
}
