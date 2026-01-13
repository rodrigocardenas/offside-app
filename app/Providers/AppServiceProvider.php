<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Blade;
use App\Helpers\DateTimeHelper;

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
        // Registrar Blade directives SIEMPRE (antes de cualquier condición)
        Blade::directive('userTime', function ($expression) {
            return "<?php echo \\App\\Helpers\\DateTimeHelper::toUserTimezone({$expression}); ?>";
        });

        // Blade directive para mostrar hora UTC
        Blade::directive('utcTime', function ($expression) {
            return "<?php echo \\App\\Helpers\\DateTimeHelper::toUTC({$expression}); ?>";
        });

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
