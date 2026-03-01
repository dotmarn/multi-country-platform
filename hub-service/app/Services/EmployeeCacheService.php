<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmployeeCacheService
{
    private const EMPLOYEE_TTL = 3600;
    private const CHECKLIST_TTL = 1800;
    private const EMPLOYEE_LIST_TTL = 3600;

    /**
     * Get cached employees for a country, or fetch from HR Service.
     */
    public function getEmployees(string $country, int $page = 1, int $perPage = 15): array
    {
        $cacheKey = "employees:{$country}:page:{$page}:per_page:{$perPage}";

        return Cache::remember($cacheKey, self::EMPLOYEE_LIST_TTL, function () use ($country, $page, $perPage) {
            Log::debug("Cache miss for employees list", ['country' => $country, 'page' => $page]);

            return app(HrServiceClient::class)->getEmployees($country, $page, $perPage);
        });
    }

    /**
     * Get all employees for a country (unpaginated, for checklist calculations).
     */
    public function getAllEmployees(string $country): array
    {
        $cacheKey = "employees:{$country}:all";

        return Cache::remember($cacheKey, self::EMPLOYEE_LIST_TTL, function () use ($country) {
            Log::debug("Cache miss for all employees", ['country' => $country]);

            $allEmployees = [];
            $page = 1;
            $hrClient = app(HrServiceClient::class);

            do {
                $response = $hrClient->getEmployees($country, $page, 100);
                $data = $response['data'] ?? [];
                $allEmployees = array_merge($allEmployees, $data);

                $lastPage = $response['meta']['last_page'] ?? 1;
                $page++;
            } while ($page <= $lastPage);

            return $allEmployees;
        });
    }

    /**
     * Get cached checklist data for a country.
     */
    public function getChecklist(string $country): ?array
    {
        return Cache::get("checklist:{$country}");
    }

    /**
     * Store checklist data in cache.
     */
    public function putChecklist(string $country, array $data): void
    {
        Cache::put("checklist:{$country}", $data, self::CHECKLIST_TTL);
    }

    /**
     * Store a single employee in cache.
     */
    public function putEmployee(int $employeeId, array $data): void
    {
        Cache::put("employee:{$employeeId}", $data, self::EMPLOYEE_TTL);
    }

    /**
     * Get a single cached employee.
     */
    public function getEmployee(int $employeeId): ?array
    {
        return Cache::get("employee:{$employeeId}");
    }

    /**
     * Invalidate all caches related to a country when an employee is created.
     */
    public function invalidateForCreated(string $country): void
    {
        $this->invalidateCountryCaches($country);
        Log::info("Cache invalidated for EmployeeCreated", ['country' => $country]);
    }

    /**
     * Invalidate caches when an employee is updated.
     */
    public function invalidateForUpdated(int $employeeId, string $country): void
    {
        Cache::forget("employee:{$employeeId}");
        $this->invalidateCountryCaches($country);
        Log::info("Cache invalidated for EmployeeUpdated", ['employee_id' => $employeeId, 'country' => $country]);
    }

    /**
     * Invalidate caches when an employee is deleted.
     */
    public function invalidateForDeleted(int $employeeId, string $country): void
    {
        Cache::forget("employee:{$employeeId}");
        $this->invalidateCountryCaches($country);
        Log::info("Cache invalidated for EmployeeDeleted", ['employee_id' => $employeeId, 'country' => $country]);
    }

    /**
     * Clear all country-level caches (employee lists + checklists).
     */
    private function invalidateCountryCaches(string $country): void
    {
        Cache::forget("checklist:{$country}");
        Cache::forget("employees:{$country}:all");

        $redis = Cache::getStore();

        if (method_exists($redis, 'getRedis')) {
            try {
                $prefix = config('cache.prefix', 'laravel_cache') . ':';
                $pattern = $prefix . "employees:{$country}:page:*";
                $keys = $redis->getRedis()->keys($pattern);

                if (!empty($keys)) {
                    $redis->getRedis()->del($keys);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to clear paginated cache via Redis pattern', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
