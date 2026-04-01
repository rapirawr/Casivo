<?php

namespace App\Providers;

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
        // Paksa HTTPS jika ada X-Forwarded-Proto https (biasanya dari Proxy/Ngrok)
        if (request()->server('HTTP_X_FORWARDED_PROTO') == 'https' || app()->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        
        // Atau secara spesifik jika host mengandung ngrok
        if (str_contains(request()->getHost(), 'ngrok')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
