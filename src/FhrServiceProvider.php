<?php

namespace Mjoc1985\Fhr;

use Illuminate\Support\ServiceProvider;
use Mjoc1985\Fhr\Contracts\ApiLogger;
use Mjoc1985\Fhr\Logging\NullApiLogger;
use Mjoc1985\Fhr\Services\FhrCartService;
use Mjoc1985\Fhr\Services\FhrInventoryService;
use Mjoc1985\Fhr\Services\FhrSearchService;

class FhrServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fhr.php', 'fhr');

        // Default no-op logger; the app may re-bind ApiLogger to its own implementation.
        $this->app->bindIf(ApiLogger::class, NullApiLogger::class);

        $this->app->bind(FhrClient::class, fn () => FhrClient::make());
        $this->app->bind(FhrSearchService::class, fn () => FhrSearchService::make());
        $this->app->bind(FhrInventoryService::class, fn () => FhrInventoryService::make());
        $this->app->bind(FhrCartService::class, fn () => FhrCartService::make());
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/fhr.php' => $this->app->configPath('fhr.php'),
            ], 'fhr-config');
        }
    }
}
