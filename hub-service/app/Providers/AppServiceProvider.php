<?php

namespace App\Providers;

use App\Services\ChecklistService;
use App\Services\EmployeeCacheService;
use App\Services\HrServiceClient;
use App\Validators\CountryValidatorFactory;
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
        //
    }
}
