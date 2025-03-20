<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class CustomRouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Rate limtit perminut for 5 request
        // throttle:custom
        RateLimiter::for('custom', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
