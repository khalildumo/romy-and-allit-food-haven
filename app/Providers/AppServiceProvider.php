<?php

namespace App\Providers;

use App\Mail\GoogleApiTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Mail::extend('gmail-api', function () {
            return new GoogleApiTransport();
        });
    }
}