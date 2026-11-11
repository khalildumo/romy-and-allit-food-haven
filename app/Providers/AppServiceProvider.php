<?php

namespace App\Providers;

use App\Mail\GoogleApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // Force HTTPS when running in production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Register custom Gmail API mail transport.
        Mail::extend('gmail-api', function () {
            return new GoogleApiTransport();
        });
    }
}
