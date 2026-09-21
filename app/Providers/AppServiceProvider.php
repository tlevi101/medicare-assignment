<?php

namespace App\Providers;

use App\Services\AvailabilitiesSlotsService;
use App\Services\ReserveAppointmentService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AvailabilitiesSlotsService::class, fn () => new AvailabilitiesSlotsService);
        $this->app->singleton(ReserveAppointmentService::class, fn () => new ReserveAppointmentService);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
