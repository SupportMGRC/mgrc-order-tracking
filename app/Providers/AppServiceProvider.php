<?php

namespace App\Providers;

use App\Models\BlockedDate;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use App\Observers\AuditObserver;
use Illuminate\Pagination\Paginator;
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
        
        Paginator::useBootstrapFive();

        // Audit logging: record create/update/delete for key models.
        $audited = [
            Order::class,
            Product::class,
            Customer::class,
            User::class,
            Visit::class,
            BlockedDate::class,
        ];

        foreach ($audited as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}