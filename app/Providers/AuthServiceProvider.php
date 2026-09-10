<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

/*
 * NOTE: Laravel 12 with the new bootstrap/app.php structure may not ship this
 * file by default. If it's missing, either:
 *   a) php artisan make:provider AuthServiceProvider  and register it in
 *      bootstrap/providers.php, OR
 *   b) Skip this class and register the policy directly via Gate::policy()
 *      inside AppServiceProvider::boot():
 *
 *      use Illuminate\Support\Facades\Gate;
 *      Gate::policy(\App\Models\Product::class, \App\Policies\ProductPolicy::class);
 */
