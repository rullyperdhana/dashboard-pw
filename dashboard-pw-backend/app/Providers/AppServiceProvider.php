<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Automated Database Repair
        if (config('app.env') === 'local' && \App\Services\SchemaService::isRepairNeeded()) {
            $schemaService = new \App\Services\SchemaService();
            $schemaService->repairAll();
        }
    }
}
