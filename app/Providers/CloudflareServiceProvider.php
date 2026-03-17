<?php

namespace App\Providers;

use App\Services\CloudflareImagesService;
use App\Helpers\CloudflareImagesHelper;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class CloudflareServiceProvider extends ServiceProvider
{
    /**
     * Register the Cloudflare service in the container.
     */
    public function register(): void
    {
        $this->app->singleton(CloudflareImagesService::class, function ($app) {
            return new CloudflareImagesService();
        });

        // Also register a short alias
        $this->app->alias(CloudflareImagesService::class, 'cloudflare-images');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();
    }

    /**
     * Register Blade directives for Cloudflare Images
     */
    protected function registerBladeDirectives(): void
    {
        /**
         * @cloudflareImage directive
         *
         * Usage:
         *   @cloudflareImage('image-id', 'Alt text')
         *   @cloudflareImage('image-id', 'Alt text', 'avatar_small')
         *   @cloudflareImage('image-id', 'Alt text', null, ['class' => 'avatar'])
         */
        Blade::directive('cloudflareImage', function ($expression) {
            return "<?php echo \App\Helpers\CloudflareImagesHelper::img({$expression}); ?>";
        });

        /**
         * @cloudflareImageResponsive directive
         *
         * Usage:
         *   @cloudflareImageResponsive('image-id', 'Alt text')
         *   @cloudflareImageResponsive('image-id', 'Alt text', 'logo')
         *   @cloudflareImageResponsive('image-id', 'Alt text', 'group_cover', ['class' => 'cover'])
         */
        Blade::directive('cloudflareImageResponsive', function ($expression) {
            return "<?php echo \App\Helpers\CloudflareImagesHelper::imgResponsive({$expression}); ?>";
        });

        /**
         * @cloudflarePicture directive (WebP + fallback)
         *
         * Usage:
         *   @cloudflarePicture('image-id', 'Alt text')
         *   @cloudflarePicture('image-id', 'Alt text', 'group_cover')
         *   @cloudflarePicture('image-id', 'Alt text', 'group_cover', ['class' => 'hero'])
         */
        Blade::directive('cloudflarePicture', function ($expression) {
            return "<?php echo \App\Helpers\CloudflareImagesHelper::picture({$expression}); ?>";
        });

        /**
         * @cloudflareUrl directive
         *
         * Usage:
         *   src="@cloudflareUrl('image-id')"
         *   src="@cloudflareUrl('image-id', ['width' => 400, 'quality' => 'auto'])"
         */
        Blade::directive('cloudflareUrl', function ($expression) {
            return "<?php echo \App\Helpers\CloudflareImagesHelper::url({$expression}); ?>";
        });

        /**
         * @cloudflareTransform directive
         *
         * Usage:
         *   src="@cloudflareTransform('image-id', 'avatar_small')"
         */
        Blade::directive('cloudflareTransform', function ($expression) {
            return "<?php echo \App\Helpers\CloudflareImagesHelper::transform({$expression}); ?>";
        });

        /**
         * @cloudflareBackground directive
         *
         * Usage:
         *   style="@cloudflareBackground('image-id')"
         *   style="@cloudflareBackground('image-id', ['width' => 1920])"
         */
        Blade::directive('cloudflareBackground', function ($expression) {
            return "<?php echo \App\Helpers\CloudflareImagesHelper::backgroundImage({$expression}); ?>";
        });
    }
}

