<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_password(): void
    {
        // Arrange
        $user = User::factory()->student()->create(['password' => Hash::make('old-password123')]);

        // Act
        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        // Assert
        $response->assertRedirect(route('settings.profile.edit', ['tab' => 'password']));
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('new-password456', $user->fresh()->password));
    }

    public function test_current_password_must_match(): void
    {
        // Arrange
        $user = User::factory()->student()->create(['password' => Hash::make('old-password123')]);

        // Act
        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        // Assert
        $response->assertSessionHasErrors(['current_password'], null, 'updatePassword');
        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_new_password_must_meet_minimum_length(): void
    {
        // Arrange
        $user = User::factory()->student()->create(['password' => Hash::make('old-password123')]);

        // Act
        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'old-password123',
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password'], null, 'updatePassword');
        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_new_password_confirmation_must_match(): void
    {
        // Arrange
        $user = User::factory()->student()->create(['password' => Hash::make('old-password123')]);

        // Act
        $response = $this->actingAs($user)->put(route('settings.password.update'), [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'different-password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password'], null, 'updatePassword');
        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_unauthenticated_request_is_redirected_to_login(): void
    {
        // Act
        $response = $this->put(route('settings.password.update'), [
            'current_password' => 'old-password123',
            'password' => 'new-password456',
            'password_confirmation' => 'new-password456',
        ]);

        // Assert
        $response->assertRedirect('/login');
    }
}
