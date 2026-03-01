<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HrServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.hr_service.url'), '/');
    }

    /**
     * Get all employees, optionally filtered by country.
     */
    public function getEmployees(?string $country = null, int $page = 1, int $perPage = 15): array
    {
        try {
            $query = ['page' => $page, 'per_page' => $perPage];

            if ($country) {
                $query['country'] = $country;
            }

            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/api/employees", $query);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('HR Service API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['data' => [], 'meta' => []];
        } catch (\Throwable $e) {
            Log::error('HR Service API connection failed', [
                'error' => $e->getMessage(),
            ]);

            return ['data' => [], 'meta' => []];
        }
    }

    /**
     * Get a single employee by ID.
     */
    public function getEmployee(int $id): ?array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/api/employees/{$id}");

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('HR Service API connection failed', [
                'error' => $e->getMessage(),
                'employee_id' => $id,
            ]);

            return null;
        }
    }
}
