<?php

namespace App\Providers;

use App\Models\ImportBatch;
use App\Models\Ticket;
use App\Policies\ImportBatchPolicy;
use App\Policies\TicketPolicy;
use App\Services\Sla\SlaCalculator;
use App\Services\Sla\SlaConfigLoader;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
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
        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(ImportBatch::class, ImportBatchPolicy::class);
    }
}
