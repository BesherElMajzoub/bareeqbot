<?php

namespace App\Providers;

use App\Contracts\Billing\BillingProvider;
use App\Services\Billing\ManualProvider;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The active tenant for the current request/job. Resolved by the
        // SetCurrentTenant middleware, or set manually inside webhook jobs.
        $this->app->singleton(TenantContext::class);

        // Billing provider seam — manual today, automated gateway later.
        $this->app->bind(BillingProvider::class, fn ($app) => match (config('bariq.billing.provider')) {
            default => $app->make(ManualProvider::class),
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
