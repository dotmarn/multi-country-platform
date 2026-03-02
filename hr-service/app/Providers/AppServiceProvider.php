<?php

namespace App\Providers;

use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RabbitMQService::class);
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
