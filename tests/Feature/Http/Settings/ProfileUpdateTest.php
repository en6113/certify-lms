<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_own_profile(): void
    {
        $student = User::factory()->student()->create([
            'name' => '旧名前',
            'bio' => '旧自己紹介',
        ]);

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), [
            'name' => '新しい名前',
            'bio' => '新しい自己紹介',
        ]);

        $response->assertRedirect(route('settings.profile.edit'));
        $response->assertSessionHas('success');
        $this->assertSame('新しい名前', $student->fresh()->name);
        $this->assertSame('新しい自己紹介', $student->fresh()->bio);
    }

    public function test_coach_can_update_meeting_url(): void
    {
        $coach = User::factory()->coach()->create(['meeting_url' => 'https://zoom.us/abc-defg-hij']);

        $response = $this->actingAs($coach)->patch(route('settings.profile.update'), [
            'name' => 'コーチ花子',
            'bio' => null,
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ]);

        $response->assertRedirect(route('settings.profile.edit'));
        $this->assertSame('https://meet.google.com/abc-defg-hij', $coach->fresh()->meeting_url);
    }

    public function test_student_sending_meeting_url_does_not_persist_it(): void
    {
        $student = User::factory()->student()->create(['meeting_url' => null]);

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), [
            'name' => '受講太郎',
            'bio' => null,
            'meeting_url' => 'https://meet.google.com/should-be-ignored',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertNull($student->fresh()->meeting_url);
    }

    public function test_graduated_student_can_still_update_profile(): void
    {
        $student = User::factory()->student()->graduated()->create();

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), [
            'name' => '卒業太郎',
            'bio' => '○○に向けて頑張っています。',
        ]);

        $response->assertRedirect(route('settings.profile.edit'));
        $this->assertSame('卒業太郎', $student->fresh()->name);
    }

    public function test_validation_errors_are_returned(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->patch(route('settings.profile.update'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_unauthenticated_request_is_redirected_to_login(): void
    {
        $response = $this->patch(route('settings.profile.update'), ['name' => '名前']);

        $response->assertRedirect('/login');
    }
}
