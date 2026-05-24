<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();

        Gate::define('access-operations', function (\App\Models\User $user) {
            return $user->hasRole(['ops', 'admin']);
        });

        Gate::define('access-commercial-review', function (\App\Models\User $user) {
            return $user->hasRole(['approver', 'admin']);
        });

        Gate::define('access-admin-settings', function (\App\Models\User $user) {
            return $user->isAdmin();
        });

        Gate::define('approve-order', function (\App\Models\User $user) {
            return $user->hasRole(['approver', 'admin']);
        });

        Gate::define('manage-users', function (\App\Models\User $user) {
            return $user->isAdmin();
        });
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
