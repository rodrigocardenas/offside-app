<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * CloudflareImages Facade
 *
 * @method static string upload(\Illuminate\Http\UploadedFile $file, string $directory = '')
 * @method static bool delete(string $imageId)
 * @method static string getUrl(string $imageId, array $options = [])
 * @method static string getTransformedUrl(string $imageId, string $transformKey)
 * @method static string getResponsiveSet(string $imageId, string $imageType = 'avatar')
 * @method static array batch(array $files, string $directory = '')
 * @method static bool isHealthy()
 *
 * @see \App\Services\CloudflareImagesService
 */
class CloudflareImages extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'cloudflare-images';
    }
}
