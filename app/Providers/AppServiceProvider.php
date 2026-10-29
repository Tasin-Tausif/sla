<?php

namespace App\Providers;

use App\Services\Sla\SlaCalculator;
use App\Services\Sla\SlaConfigLoader;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            SlaCalculator::class,
            fn (): SlaCalculator => new SlaCalculator(SlaConfigLoader::load()),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
