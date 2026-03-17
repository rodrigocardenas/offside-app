<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class CloudflareImagesService
{
    protected string $accountId;
    protected string $apiToken;
    protected string $apiUrl;
    protected string $imagesDomain;
    protected bool $enabled;
    protected bool $fallbackEnabled;
    protected string $fallbackDisk;

    public function __construct()
    {
        $this->accountId = config('cloudflare.account_id');
        $this->apiToken = config('cloudflare.api_token');
        $this->apiUrl = config('cloudflare.api_url');
        $this->imagesDomain = config('cloudflare.images_domain');
        $this->enabled = config('cloudflare.enabled');
        $this->fallbackEnabled = config('cloudflare.enable_fallback');
        $this->fallbackDisk = config('cloudflare.fallback_disk');
    }

    /**
     * Upload a single image to Cloudflare Images
     *
     * @param UploadedFile $file
     * @param string $directory Optional directory prefix
     * @return string Image ID from Cloudflare
     * @throws Exception
     */
    public function upload(UploadedFile $file, string $directory = ''): string
    {
        if (!$this->enabled) {
            $this->logWarning('Cloudflare Images is disabled, using fallback');
            return $this->uploadToLocalStorage($file, $directory);
        }

        try {
            $this->validateFile($file);

            $imageId = $this->uploadToCloudflare($file, $directory);

            $this->logInfo('Image uploaded to Cloudflare successfully', [
                'image_id' => $imageId,
                'directory' => $directory,
                'original_name' => $file->getClientOriginalName(),
            ]);

            return $imageId;
        } catch (Exception $e) {
            $this->logError('Error uploading to Cloudflare', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
            ]);

            if ($this->fallbackEnabled) {
                $this->logInfo('Falling back to local storage');
                return $this->uploadToLocalStorage($file, $directory);
            }

            throw $e;
        }
    }

    /**
     * Delete an image from Cloudflare Images
     *
     * @param string $imageId
     * @return bool
     */
    public function delete(string $imageId): bool
    {
        if (!$this->enabled) {
            return true;
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(config('cloudflare.upload.timeout', 30))
                ->delete("{$this->apiUrl}/accounts/{$this->accountId}/images/v1/{$imageId}");

            if (!$response->successful()) {
                $this->logError('Failed to delete image from Cloudflare', [
                    'image_id' => $imageId,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                return false;
            }

            $this->logInfo('Image deleted from Cloudflare', ['image_id' => $imageId]);
            return true;
        } catch (Exception $e) {
            $this->logError('Error deleting image from Cloudflare', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Generate a Cloudflare Images URL with optional transformations
     *
     * @param string $imageId
     * @param array $options Transform options
     * @return string
     */
    public function getUrl(string $imageId, array $options = []): string
    {
        if (!$this->enabled) {
            return $this->getLocalUrl($imageId);
        }

        try {
            $transformString = $this->buildTransformString($options);
            $url = "{$this->imagesDomain}/cdn-cgi/image/{$transformString}/{$imageId}";

            if (config('cloudflare.cache.enabled')) {
                // Cache URL in applications cache
                $cacheKey = "cloudflare_url_{$imageId}_" . md5(json_encode($options));
                return \Illuminate\Support\Facades\Cache::remember($cacheKey, config('cloudflare.cache.ttl'), function () use ($url) {
                    return $url;
                });
            }

            return $url;
        } catch (Exception $e) {
            $this->logError('Error generating Cloudflare URL', [
                'image_id' => $imageId,
                'error' => $e->getMessage(),
            ]);

            if ($this->fallbackEnabled) {
                return $this->getLocalUrl($imageId);
            }

            return '';
        }
    }

    /**
     * Get a predefined transformation URL
     *
     * @param string $imageId
     * @param string $transformKey Key from config('cloudflare.transforms')
     * @return string
     */
    public function getTransformedUrl(string $imageId, string $transformKey): string
    {
        $transforms = config("cloudflare.transforms.{$transformKey}");

        if (!$transforms) {
            $this->logWarning("Requested transform not found: {$transformKey}");
            return $this->getUrl($imageId);
        }

        return $this->getUrl($imageId, $transforms);
    }

    /**
     * Generate responsive image srcset
     *
     * @param string $imageId
     * @param string $imageType Type of image (avatar, logo, group_cover)
     * @return string srcset string for img tag
     */
    public function getResponsiveSet(string $imageId, string $imageType = 'avatar'): string
    {
        $widths = config("cloudflare.responsive_widths.{$imageType}", []);

        if (empty($widths)) {
            return $this->getUrl($imageId);
        }

        $srcset = array_map(function ($width) use ($imageId) {
            $url = $this->getUrl($imageId, ['width' => $width, 'quality' => 'auto']);
            return "{$url} {$width}w";
        }, $widths);

        return implode(', ', $srcset);
    }

    /**
     * Batch upload multiple images
     *
     * @param array $files Array of UploadedFile objects
     * @param string $directory
     * @return array ['success' => [...], 'failed' => [...]]
     */
    public function batch(array $files, string $directory = ''): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($files as $file) {
            try {
                $imageId = $this->upload($file, $directory);
                $results['success'][] = $imageId;
            } catch (Exception $e) {
                $results['failed'][] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Check if Cloudflare Images is available and properly configured
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->accountId || !$this->apiToken || !$this->imagesDomain) {
            return false;
        }

        try {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->get("{$this->apiUrl}/accounts/{$this->accountId}/images/v1");

            return $response->successful();
        } catch (Exception $e) {
            $this->logError('Cloudflare Images health check failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Upload to local storage (fallback)
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string Stored path
     */
    protected function uploadToLocalStorage(UploadedFile $file, string $directory): string
    {
        $filename = $this->generateFilename($file, $directory);
        $path = $file->storeAs($directory, basename($filename), $this->fallbackDisk);

        $this->logInfo('Image uploaded to local storage (fallback)', [
            'path' => $path,
            'disk' => $this->fallbackDisk,
        ]);

        return $path;
    }

    /**
     * Get local storage URL
     *
     * @param string $path
     * @return string
     */
    protected function getLocalUrl(string $path): string
    {
        return Storage::disk($this->fallbackDisk)->url($path);
    }

    /**
     * Upload to Cloudflare using API
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string Image ID
     * @throws Exception
     */
    protected function uploadToCloudflare(UploadedFile $file, string $directory): string
    {
        $maxRetries = config('cloudflare.upload.retries', 3);
        $retryDelay = config('cloudflare.upload.retry_delay', 1000);
        $timeout = config('cloudflare.upload.timeout', 30);

        $filename = $this->generateFilename($file, $directory);
        $metadata = json_encode([
            'original_name' => $file->getClientOriginalName(),
            'uploaded_at' => now()->toIso8601String(),
        ]);

        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withToken($this->apiToken)
                    ->timeout($timeout)
                    ->attach('file', $file->getContent(), $file->getClientOriginalName())
                    ->post("{$this->apiUrl}/accounts/{$this->accountId}/images/v1", [
                        'filename' => $filename,
                        'metadata' => $metadata,
                    ]);

                if ($response->successful()) {
                    return $response->json('result.id');
                }

                $lastException = new Exception(
                    "Cloudflare API error: " . $response->json('errors.0.message', 'Unknown error')
                );

                if ($attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                }
            } catch (Exception $e) {
                $lastException = $e;

                if ($attempt < $maxRetries) {
                    usleep($retryDelay * 1000);
                }
            }
        }

        throw $lastException ?? new Exception('Unknown error uploading to Cloudflare');
    }

    /**
     * Validate file before upload
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            throw new Exception("Invalid file type: {$file->getMimeType()}");
        }

        // Check magic bytes for extra security
        $this->validateMagicBytes($file);
    }

    /**
     * Validate file magic bytes to prevent spoofed images
     *
     * @param UploadedFile $file
     * @throws Exception
     */
    protected function validateMagicBytes(UploadedFile $file): void
    {
        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) {
            throw new Exception('Could not read file');
        }

        $header = fread($handle, 12);
        fclose($handle);

        // JPEG: FF D8 FF
        // PNG: 89 50 4E 47
        // GIF: 47 49 46
        // WebP: RIFF ... WEBP
        // SVG: XML header or <svg

        $validSignatures = [
            'jpeg' => [0xFF, 0xD8, 0xFF],
            'png' => [0x89, 0x50, 0x4E, 0x47],
            'gif' => [0x47, 0x49, 0x46],
        ];

        $isValid = false;
        foreach ($validSignatures as $format => $signature) {
            $match = true;
            foreach ($signature as $byte) {
                if (ord($header[0]) !== $byte) {
                    $match = false;
                    break;
                }
                $header = substr($header, 1);
            }
            if ($match) {
                $isValid = true;
                break;
            }
        }

        if (!$isValid && strpos($file->getMimeType(), 'webp') === false && strpos($file->getMimeType(), 'svg') === false) {
            throw new Exception('Invalid image format (failed magic bytes check)');
        }
    }

    /**
     * Generate unique filename
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string
     */
    protected function generateFilename(UploadedFile $file, string $directory = ''): string
    {
        $extension = $file->getClientOriginalExtension();
        $prefix = $directory ? "{$directory}_" : '';

        return $prefix . Str::uuid() . '.' . $extension;
    }

    /**
     * Build transformation query string
     *
     * @param array $options
     * @return string
     */
    protected function buildTransformString(array $options): string
    {
        if (empty($options)) {
            return '';
        }

        $params = [];
        foreach ($options as $key => $value) {
            if ($value !== null && $value !== '') {
                $params[] = "{$key}={$value}";
            }
        }

        return implode(',', $params);
    }

    /**
     * Log info message
     */
    protected function logInfo(string $message, array $context = []): void
    {
        if (config('cloudflare.logging.enabled')) {
            Log::channel(config('cloudflare.log_channel', 'stack'))
                ->info("[CloudflareImages] {$message}", $context);
        }
    }

    /**
     * Log warning message
     */
    protected function logWarning(string $message, array $context = []): void
    {
        if (config('cloudflare.logging.enabled')) {
            Log::channel(config('cloudflare.log_channel', 'stack'))
                ->warning("[CloudflareImages] {$message}", $context);
        }
    }

    /**
     * Log error message
     */
    protected function logError(string $message, array $context = []): void
    {
        if (config('cloudflare.logging.enabled')) {
            Log::channel(config('cloudflare.log_channel', 'stack'))
                ->error("[CloudflareImages] {$message}", $context);
        }
    }
}
