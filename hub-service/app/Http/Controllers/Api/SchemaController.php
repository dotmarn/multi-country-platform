<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventTypesEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CountryRequest;
use App\Http\Resources\SchemaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SchemaController extends Controller
{
    public function show(CountryRequest $request, string $stepId): JsonResponse
    {
        $country = $request->validated('country');

        $steps = config("countries.{$country}.steps", []);
        $stepExists = collect($steps)->contains('id', $stepId);

        if (!$stepExists) {
            return response()->error(
                Response::HTTP_NOT_FOUND,
                "Step '{$stepId}' not found for country '{$country}'."
            );
        }

        $schema = match ($stepId) {
            'dashboard' => $this->getDashboardSchema($country),
            'employees' => $this->getEmployeesSchema($country),
            'documentation' => $this->getDocumentationSchema($country),
            default => ['widgets' => []],
        };

        return response()->success(
            Response::HTTP_OK,
            "Schema for step '{$stepId}' fetched successfully.",
            new SchemaResource([
                'country' => $country,
                'step_id' => $stepId,
                'schema' => $schema,
            ])
        );
    }

    private function getDashboardSchema(string $country): array
    {
        return [
            'type' => 'dashboard',
            'widgets' => config("countries.{$country}.dashboard_widgets", []),
        ];
    }

    private function getEmployeesSchema(string $country): array
    {
        return [
            'type' => 'data_table',
            'widgets' => [
                [
                    'id' => 'employee_table',
                    'type' => 'table',
                    'title' => 'Employees',
                    'data_source' => "/api/employees?country={$country}",
                    'columns' => config("countries.{$country}.columns", []),
                    'refresh_channel' => "country.{$country}",
                    'refresh_event' => EventTypesEnum::EMPLOYEE_LIST_UPDATED->value,
                    'pagination' => true,
                    'size' => 'full',
                    'position' => ['row' => 1, 'col' => 1],
                ],
            ],
        ];
    }

    private function getDocumentationSchema(string $country): array
    {
        return [
            'type' => 'content',
            'widgets' => [
                [
                    'id' => 'documentation_content',
                    'type' => 'markdown',
                    'title' => 'Documentation',
                    'content' => 'Company documentation and guidelines.',
                    'size' => 'full',
                    'position' => ['row' => 1, 'col' => 1],
                ],
            ],
        ];
    }
}
