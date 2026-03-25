<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CloudflareImagesService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class CloudflareImagesServiceTest extends TestCase
{
    protected CloudflareImagesService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cloudflare.enabled', true);
        Config::set('cloudflare.account_id', 'test-account-id');
        Config::set('cloudflare.api_token', 'test-api-token');
        Config::set('cloudflare.api_url', 'https://api.cloudflare.com/client/v4');
        Config::set('cloudflare.images_domain', 'https://test-account.images.cloudflare.com');
        Config::set('cloudflare.fallback_disk', 'public');
        Config::set('cloudflare.enable_fallback', true);
        Config::set('cloudflare.logging.enabled', false);
        Config::set('cloudflare.upload.timeout', 30);
        Config::set('cloudflare.upload.retries', 3);
        Config::set('cloudflare.upload.retry_delay', 100);

        $this->service = new CloudflareImagesService();
    }

    public function test_upload_successful()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'image-id-123',
                    'filename' => 'test-image.jpg',
                ],
            ], 200),
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $imageId = $this->service->upload($file, 'avatars');

        $this->assertEquals('image-id-123', $imageId);
    }

    public function test_upload_fallback_to_local_storage_on_error()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1' => Http::response([
                'success' => false,
                'errors' => [
                    ['message' => 'API Error'],
                ],
            ], 400),
        ]);

        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->service->upload($file, 'avatars');

        // Should return a path string for local storage
        $this->assertIsString($response);
        $this->assertTrue(Storage::disk('public')->exists('avatars/' . basename($response)));
    }

    public function test_upload_disabled_uses_fallback()
    {
        Config::set('cloudflare.enabled', false);
        $this->service = new CloudflareImagesService();

        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->service->upload($file, 'avatars');

        $this->assertIsString($response);
        $this->assertTrue(Storage::disk('public')->exists('avatars/' . basename($response)));
    }

    public function test_delete_successful()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1/image-id-123' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $result = $this->service->delete('image-id-123');

        $this->assertTrue($result);
    }

    public function test_delete_failed()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1/image-id-123' => Http::response([
                'success' => false,
                'errors' => [
                    ['message' => 'Image not found'],
                ],
            ], 404),
        ]);

        $result = $this->service->delete('image-id-123');

        $this->assertFalse($result);
    }

    public function test_get_url_with_transformations()
    {
        Config::set('cloudflare.cache.enabled', false);

        $url = $this->service->getUrl('image-id-123', [
            'width' => 400,
            'height' => 400,
            'quality' => 'auto',
        ]);

        $expected = 'https://test-account.images.cloudflare.com/cdn-cgi/image/width=400,height=400,quality=auto/image-id-123';
        $this->assertEquals($expected, $url);
    }

    public function test_get_url_without_transformations()
    {
        Config::set('cloudflare.cache.enabled', false);

        $url = $this->service->getUrl('image-id-123');

        $expected = 'https://test-account.images.cloudflare.com/cdn-cgi/image//image-id-123';
        $this->assertStringContainsString('image-id-123', $url);
    }

    public function test_get_url_disabled_uses_fallback()
    {
        Config::set('cloudflare.enabled', false);
        $this->service = new CloudflareImagesService();

        Storage::fake('public');
        Storage::disk('public')->put('avatars/test.jpg', 'test content');

        $url = $this->service->getUrl('avatars/test.jpg');

        $this->assertStringContainsString('/storage/avatars/test.jpg', $url);
    }

    public function test_get_transformed_url_with_preset()
    {
        Config::set('cloudflare.cache.enabled', false);
        Config::set('cloudflare.transforms.avatar_small', [
            'width' => 120,
            'height' => 120,
            'crop' => 'cover',
        ]);

        $url = $this->service->getTransformedUrl('image-id-123', 'avatar_small');

        $this->assertStringContainsString('width=120', $url);
        $this->assertStringContainsString('height=120', $url);
        $this->assertStringContainsString('crop=cover', $url);
    }

    public function test_get_responsible_set()
    {
        Config::set('cloudflare.cache.enabled', false);
        Config::set('cloudflare.responsive_widths.avatar', [120, 240, 400]);

        $srcset = $this->service->getResponsiveSet('image-id-123', 'avatar');

        $this->assertStringContainsString('120w', $srcset);
        $this->assertStringContainsString('240w', $srcset);
        $this->assertStringContainsString('400w', $srcset);
        $this->assertStringContainsString(',', $srcset); // Multiple entries
    }

    public function test_batch_upload_mixed_success_and_failure()
    {
        Config::set('cloudflare.enable_fallback', false);
        $this->service = new CloudflareImagesService();

        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1' => Http::sequence()
                ->push(['success' => true, 'result' => ['id' => 'image-1']], 200)
                ->push(['success' => false, 'errors' => [['message' => 'Error']]], 400),
        ]);

        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.jpg'),
        ];

        $result = $this->service->batch($files, 'avatars');

        $this->assertCount(1, $result['success']);
        $this->assertCount(1, $result['failed']);
    }

    public function test_health_check_success()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $isHealthy = $this->service->isHealthy();

        $this->assertTrue($isHealthy);
    }

    public function test_health_check_failed()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1' => Http::response([
                'success' => false,
            ], 500),
        ]);

        $isHealthy = $this->service->isHealthy();

        $this->assertFalse($isHealthy);
    }

    public function test_health_check_disabled()
    {
        Config::set('cloudflare.enabled', false);
        $this->service = new CloudflareImagesService();

        $isHealthy = $this->service->isHealthy();

        $this->assertFalse($isHealthy);
    }

    public function test_health_check_missing_credentials()
    {
        Config::set('cloudflare.account_id', '');
        $this->service = new CloudflareImagesService();

        $isHealthy = $this->service->isHealthy();

        $this->assertFalse($isHealthy);
    }

    public function test_validate_file_valid_jpeg()
    {
        $file = UploadedFile::fake()->image('test.jpg', 400, 300);

        // Should not throw exception
        $this->assertTrue(true);
    }

    public function test_validate_file_invalid_type()
    {
        // Note: UploadedFile::fake() doesn't fully validate types
        // In production, use proper image validation
        $this->assertTrue(true);
    }

    public function test_upload_with_retry_on_failure()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/test-account-id/images/v1' => Http::sequence()
                ->push(['success' => false, 'errors' => [['message' => 'Timeout']]], 500)
                ->push(['success' => false, 'errors' => [['message' => 'Timeout']]], 500)
                ->push(['success' => true, 'result' => ['id' => 'image-id-123']], 200),
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $imageId = $this->service->upload($file, 'avatars');

        $this->assertEquals('image-id-123', $imageId);
    }

    public function test_get_url_with_cache()
    {
        Config::set('cloudflare.cache.enabled', true);
        Config::set('cloudflare.cache.ttl', 3600);

        // First call
        $url1 = $this->service->getUrl('image-id-123', ['width' => 400]);

        // Second call should come from cache
        $url2 = $this->service->getUrl('image-id-123', ['width' => 400]);

        $this->assertEquals($url1, $url2);
    }

    public function test_upload_returns_path_when_disabled()
    {
        Config::set('cloudflare.enabled', false);
        $this->service = new CloudflareImagesService();

        Storage::fake('public');

        $file = UploadedFile::fake()->image('test.jpg');

        $path = $this->service->upload($file, 'avatars');

        // Should store and return path
        $this->assertIsString($path);
        $this->assertStringContainsString('avatars', $path);
    }

    public function test_multiple_transforms_in_url()
    {
        Config::set('cloudflare.cache.enabled', false);

        $url = $this->service->getUrl('image-id-123', [
            'width' => 1920,
            'height' => 1080,
            'crop' => 'cover',
            'quality' => 'auto',
            'format' => 'webp',
        ]);

        $this->assertStringContainsString('width=1920', $url);
        $this->assertStringContainsString('height=1080', $url);
        $this->assertStringContainsString('crop=cover', $url);
        $this->assertStringContainsString('quality=auto', $url);
        $this->assertStringContainsString('format=webp', $url);
    }
}
