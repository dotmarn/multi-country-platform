<?php

namespace App\Http\Controllers\Api;

use App\Enums\CountryEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use App\Services\EmployeeCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeCacheService $cacheService,
    ) {}

    public function index(CountryRequest $request): JsonResponse
    {
        $country = $request->validated('country');
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 15);

        $columns = config("countries.{$country}.columns", []);

        $employeeData = $this->cacheService->getEmployees($country, $page, $perPage);

        if ($country === CountryEnum::COUNTRY_USA->value && isset($employeeData['data'])) {
            $employeeData['data'] = array_map(function ($employee) {
                if (!empty($employee['ssn'])) {
                    $employee['ssn'] = '***-**-' . substr($employee['ssn'], -4);
                }
                return $employee;
            }, $employeeData['data']);
        }

        return response()->success(
            Response::HTTP_OK,
            "Employees fetched successfully for country",
            [
                'country' => $country,
                'columns' => $columns,
                'data' => $employeeData['data'] ?? [],
                'meta' => $employeeData['meta'] ?? [],
            ]
        );
    }
}
