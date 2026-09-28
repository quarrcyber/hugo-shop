<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Gate::define('manage-catalog', fn (User $user): bool => in_array($user->role, ['staff', 'admin'], true));
        Gate::define('manage-orders', fn (User $user): bool => in_array($user->role, ['staff', 'admin'], true));
        Gate::define('moderate-reviews', fn (User $user): bool => in_array($user->role, ['staff', 'admin'], true));
        Gate::define('manage-users', fn (User $user): bool => $user->role === 'admin');
    }
}
