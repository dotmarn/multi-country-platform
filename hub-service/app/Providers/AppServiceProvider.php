<?php

namespace App\Providers;

use App\Services\ChecklistService;
use App\Services\EmployeeCacheService;
use App\Services\HrServiceClient;
use App\Validators\CountryValidatorFactory;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(HrServiceClient::class);
        $this->app->singleton(EmployeeCacheService::class);
        $this->app->singleton(CountryValidatorFactory::class);
        $this->app->singleton(ChecklistService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Response::macro('success', function ($status, $message, $data = [], $meta = null) {
            return Response::json(['status' => true, 'message' => $message, 'data' => $data, 'meta' => $meta], $status);
        });

        Response::macro('error', function ($status, $message, $error = []) {
            return Response::json(['status' => false, 'message' => $message, 'errors' => $error], $status);
        });
    }
}
