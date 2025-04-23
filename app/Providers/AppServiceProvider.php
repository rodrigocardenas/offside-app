<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

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
        if ($this->app->runningInConsole()) {
            return;
        }

        // Forzar la ubicación del manifest
        $manifestPath = public_path('build/manifest.json');
        $viteManifestPath = public_path('build/.vite/manifest.json');

        if (File::exists($viteManifestPath) && !File::exists($manifestPath)) {
            File::copy($viteManifestPath, $manifestPath);
        }
    }
}
