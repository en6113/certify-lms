<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_avatar(): void
    {
        // Arrange
        Storage::fake('public');

        $user = User::factory()->student()->create();
        $file = UploadedFile::fake()->image('avatar.png', 200, 200);

        // Act
        $response = $this->actingAs($user)
            ->post(route('settings.avatar.store'), ['avatar' => $file]);

        // Assert
        $response->assertRedirect(route('settings.profile.edit'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotNull($user->avatar_url);
        $path = ltrim(str_replace('/storage/', '', $user->avatar_url), '/');
        Storage::disk('public')->assertExists($path);
    }

    public function test_rejects_missing_file(): void
    {
        // Arrange
        Storage::fake('public');
        $user = User::factory()->student()->create();

        // Act
        $response = $this->actingAs($user)->post(route('settings.avatar.store'), []);

        // Assert
        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_rejects_oversized_file(): void
    {
        // Arrange
        Storage::fake('public');
        $user = User::factory()->student()->create();
        $file = UploadedFile::fake()->create('big.png', 3000, 'image/png');

        // Act
        $response = $this->actingAs($user)
            ->post(route('settings.avatar.store'), ['avatar' => $file]);

        // Assert
        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_rejects_invalid_mime(): void
    {
        // Arrange
        Storage::fake('public');
        $user = User::factory()->student()->create();
        $file = UploadedFile::fake()->create('script.svg', 10, 'image/svg+xml');

        // Act
        $response = $this->actingAs($user)
            ->post(route('settings.avatar.store'), ['avatar' => $file]);

        // Assert
        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_uploading_new_avatar_deletes_old_file(): void
    {
        // Arrange
        Storage::fake('public');
        $user = User::factory()->student()->create();

        $this->actingAs($user)->post(route('settings.avatar.store'), [
            'avatar' => UploadedFile::fake()->image('first.png', 200, 200),
        ]);
        $user->refresh();
        $oldPath = ltrim(str_replace('/storage/', '', $user->avatar_url), '/');
        Storage::disk('public')->assertExists($oldPath);

        // Act
        $this->actingAs($user)->post(route('settings.avatar.store'), [
            'avatar' => UploadedFile::fake()->image('second.png', 200, 200),
        ]);

        // Assert
        $user->refresh();
        $newPath = ltrim(str_replace('/storage/', '', $user->avatar_url), '/');
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }
}
