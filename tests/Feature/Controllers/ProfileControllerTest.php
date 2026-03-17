<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * Test avatar upload with Cloudflare enabled
     */
    public function test_avatar_upload_with_cloudflare_enabled()
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'test-cloudflare-id-123',
                    'filename' => 'test-avatar.jpg',
                    'uploaded' => now()->toIso8601String(),
                ],
            ], 200),
        ]);

        // Enable Cloudflare in config
        config(['cloudflare.images.enabled' => true]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'light',
            'theme_mode' => 'light',
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('profile.edit'));
        
        $user->refresh();
        $this->assertEquals('cloudflare', $user->avatar_provider);
        $this->assertEquals('test-cloudflare-id-123', $user->avatar_cloudflare_id);
        $this->assertNull($user->avatar);

        // Verify HTTP request was made to Cloudflare
        Http::assertSent(function ($request) {
            return $request->url() === config('cloudflare.images.api_url') . 'accounts/' . config('cloudflare.account_id') . '/images/v1';
        });
    }

    /**
     * Test avatar upload with Cloudflare fallback to local storage
     */
    public function test_avatar_upload_cloudflare_fallback_to_local()
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => false,
                'errors' => [['message' => 'API Error']],
            ], 400),
        ]);

        config(['cloudflare.images.enabled' => true]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'light',
            'theme_mode' => 'light',
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        // Should fallback to local storage
        $this->assertEquals('local', $user->avatar_provider);
        $this->assertNull($user->avatar_cloudflare_id);
        $this->assertNotNull($user->avatar);
        $this->assertTrue(Storage::disk('public')->exists('avatars/' . $user->avatar));
    }

    /**
     * Test avatar upload with Cloudflare disabled (local storage)
     */
    public function test_avatar_upload_with_cloudflare_disabled()
    {
        config(['cloudflare.images.enabled' => false]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'light',
            'theme_mode' => 'light',
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        // Should use local storage
        $this->assertEquals('local', $user->avatar_provider);
        $this->assertNull($user->avatar_cloudflare_id);
        $this->assertNotNull($user->avatar);
        $this->assertTrue(Storage::disk('public')->exists('avatars/' . $user->avatar));
    }

    /**
     * Test switching from Cloudflare to local storage
     */
    public function test_switch_from_cloudflare_to_local()
    {
        config(['cloudflare.images.enabled' => true]);

        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => ['id' => 'delete-id'],
            ], 200),
        ]);

        $user = User::factory()->create([
            'avatar_provider' => 'cloudflare',
            'avatar_cloudflare_id' => 'old-cloudflare-id',
        ]);
        $this->actingAs($user);

        // Disable Cloudflare
        config(['cloudflare.images.enabled' => false]);

        $file = UploadedFile::fake()->image('avatar.jpg', 500, 500);

        $response = $this->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'theme' => 'light',
            'theme_mode' => 'light',
            'avatar' => $file,
        ]);

        $response->assertRedirect(route('profile.edit'));

        $user->refresh();
        // Should switch to local storage
        $this->assertEquals('local', $user->avatar_provider);
        $this->assertNull($user->avatar_cloudflare_id);
        $this->assertNotNull($user->avatar);
        $this->assertTrue(Storage::disk('public')->exists('avatars/' . $user->avatar));
    }

    /**
     * Test getUserAvatarUrl method
     */
    public function test_get_user_avatar_url()
    {
        Http::fake([
            'api.cloudflare.com/*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'test-id-456',
                ],
            ], 200),
        ]);

        config(['cloudflare.images.enabled' => true]);

        $user = User::factory()->create([
            'avatar_provider' => 'cloudflare',
            'avatar_cloudflare_id' => 'test-cloudflare-id',
        ]);

        $url = $user->getAvatarUrl('small');
        $this->assertStringContainsString('offsideclub.es', $url);
        $this->assertStringContainsString('test-cloudflare-id', $url);
    }

    /**
     * Test local storage avatar fallback
     */
    public function test_local_storage_avatar_fallback()
    {
        $user = User::factory()->create([
            'avatar' => 'test-avatar.jpg',
            'avatar_provider' => 'local',
        ]);

        Storage::disk('public')->put('avatars/test-avatar.jpg', 'fake content');

        $url = $user->getAvatarUrl();
        $this->assertStringContainsString('storage/avatars/test-avatar.jpg', $url);
    }

    /**
     * Test default avatar when no image provided
     */
    public function test_default_avatar_fallback()
    {
        $user = User::factory()->create([
            'avatar' => null,
            'avatar_cloudflare_id' => null,
        ]);

        $url = $user->getAvatarUrl();
        $this->assertStringContainsString('ui-avatars.com', $url);
        $this->assertStringContainsString(urlencode($user->name), $url);
    }
}
