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
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $tz = \App\Models\Setting::getValue('timezone', 'America/Managua');
                if ($tz) {
                    date_default_timezone_set($tz);
                    config(['app.timezone' => $tz]);
                }
            }
        } catch (\Throwable $e) {
            // Silence if DB is not ready during migrations
        }
    }
}
