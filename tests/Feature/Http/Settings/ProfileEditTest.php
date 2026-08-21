<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $student = User::factory()->student()->create(['name' => '受講太郎']);

        $response = $this->actingAs($student)->get(route('settings.profile.edit'));

        $response->assertOk();
        $response->assertViewIs('settings.profile');
        $response->assertSee('受講太郎');
    }

    public function test_coach_sees_meeting_url_field(): void
    {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('settings.profile.edit'));

        $response->assertOk();
        $response->assertSee('name="meeting_url"', false);
    }

    public function test_student_does_not_see_meeting_url_field(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get(route('settings.profile.edit'));

        $response->assertOk();
        $response->assertDontSee('name="meeting_url"', false);
    }

    public function test_graduated_student_can_view_profile_page(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $response = $this->actingAs($student)->get(route('settings.profile.edit'));

        $response->assertOk();
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('settings.profile.edit'));

        $response->assertRedirect('/login');
    }
}
