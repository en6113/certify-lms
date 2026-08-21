<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarDestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_own_avatar(): void
    {
        // Arrange
        Storage::fake('public');
        $user = User::factory()->student()->create();
        $this->actingAs($user)->post(route('settings.avatar.store'), [
            'avatar' => UploadedFile::fake()->image('avatar.png', 200, 200),
        ]);
        $user->refresh();
        $path = ltrim(str_replace('/storage/', '', $user->avatar_url), '/');
        Storage::disk('public')->assertExists($path);

        // Act
        $response = $this->actingAs($user)->delete(route('settings.avatar.destroy'));

        // Assert
        $response->assertRedirect(route('settings.profile.edit'));
        $response->assertSessionHas('success');
        $this->assertNull($user->fresh()->avatar_url);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_deleting_without_avatar_does_not_error(): void
    {
        // Arrange
        Storage::fake('public');
        $user = User::factory()->student()->create(['avatar_url' => null]);

        // Act
        $response = $this->actingAs($user)->delete(route('settings.avatar.destroy'));

        // Assert
        $response->assertRedirect(route('settings.profile.edit'));
        $this->assertNull($user->fresh()->avatar_url);
    }

    public function test_unauthenticated_request_is_redirected_to_login(): void
    {
        $response = $this->delete(route('settings.avatar.destroy'));

        $response->assertRedirect('/login');
    }
}
