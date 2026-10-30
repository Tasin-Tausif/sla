<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Services\Sla\SlaCalculator;
use App\Services\Sla\SlaConfigLoader;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Ticket::class, TicketPolicy::class);
    }
}
