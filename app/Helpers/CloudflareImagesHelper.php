<?php

namespace App\Helpers;

use App\Facades\CloudflareImages;

class CloudflareImagesHelper
{
    /**
     * Generate a Cloudflare image URL with optional transformations
     *
     * @param string $imageId
     * @param array $options
     * @return string
     */
    public static function url(string $imageId, array $options = []): string
    {
        return CloudflareImages::getUrl($imageId, $options);
    }

    /**
     * Generate a Cloudflare image URL with a predefined transform preset
     *
     * @param string $imageId
     * @param string $transformKey
     * @return string
     */
    public static function transform(string $imageId, string $transformKey): string
    {
        return CloudflareImages::getTransformedUrl($imageId, $transformKey);
    }

    /**
     * Generate a responsive srcset for an image
     *
     * @param string $imageId
     * @param string $imageType
     * @return string
     */
    public static function responsive(string $imageId, string $imageType = 'avatar'): string
    {
        return CloudflareImages::getResponsiveSet($imageId, $imageType);
    }

    /**
     * Generate an <img> tag with Cloudflare image URL
     *
     * @param string $imageId
     * @param string|null $alt
     * @param string|null $transformKey
     * @param array $attributes
     * @return string
     */
    public static function img(
        string $imageId,
        ?string $alt = '',
        ?string $transformKey = null,
        array $attributes = []
    ): string {
        $url = $transformKey
            ? self::transform($imageId, $transformKey)
            : self::url($imageId);

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= " {$key}=\"{$value}\"";
        }

        return "<img src=\"{$url}\" alt=\"{$alt}\"{$attrs} />";
    }

    /**
     * Generate an <img> tag with srcset for responsive images
     *
     * @param string $imageId
     * @param string|null $alt
     * @param string $imageType
     * @param array $attributes
     * @return string
     */
    public static function imgResponsive(
        string $imageId,
        ?string $alt = '',
        string $imageType = 'avatar',
        array $attributes = []
    ): string {
        $srcset = self::responsive($imageId, $imageType);
        $url = self::url($imageId);

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= " {$key}=\"{$value}\"";
        }

        return "<img src=\"{$url}\" srcset=\"{$srcset}\" alt=\"{$alt}\"{$attrs} />";
    }

    /**
     * Generate a <picture> tag with WebP and fallback formats
     *
     * @param string $imageId
     * @param string|null $alt
     * @param string $imageType
     * @param array $attributes
     * @return string
     */
    public static function picture(
        string $imageId,
        ?string $alt = '',
        string $imageType = 'avatar',
        array $attributes = []
    ): string {
        $srcsetWebp = self::responsive($imageId, $imageType)
            . '&format=webp';

        $srcsetJpg = self::responsive($imageId, $imageType);

        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= " {$key}=\"{$value}\"";
        }

        $url = self::url($imageId, ['format' => 'auto']);

        return "
            <picture>
                <source srcset=\"{$srcsetWebp}\" type=\"image/webp\" />
                <source srcset=\"{$srcsetJpg}\" type=\"image/jpeg\" />
                <img src=\"{$url}\" alt=\"{$alt}\"{$attrs} />
            </picture>
        ";
    }

    /**
     * Check if Cloudflare Images is available
     *
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return CloudflareImages::isHealthy();
    }

    /**
     * Get a CSS background-image URL
     *
     * @param string $imageId
     * @param array $options
     * @return string
     */
    public static function backgroundImage(string $imageId, array $options = []): string
    {
        $url = self::url($imageId, $options);
        return "background-image: url('{$url}');";
    }
}
