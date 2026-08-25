<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnarchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_unarchives_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.unarchive', $meetingPack));

        $response->assertRedirect(route('admin.meeting-packs.show', $meetingPack));
        $this->assertSame('draft', $meetingPack->fresh()->status->value);
        $this->assertSame($admin->id, $meetingPack->fresh()->updated_by_user_id);
    }

    public function test_cannot_unarchive_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.meeting-packs.unarchive', $meetingPack));

        $response->assertStatus(409);
    }

    public function test_cannot_unarchive_published(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.meeting-packs.unarchive', $meetingPack));

        $response->assertStatus(409);
    }

    public function test_coach_cannot_unarchive(): void
    {
        $coach = User::factory()->coach()->create();
        $meetingPack = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($coach)->post(route('admin.meeting-packs.unarchive', $meetingPack));

        $response->assertForbidden();
    }

    public function test_student_cannot_unarchive(): void
    {
        $student = User::factory()->student()->create();
        $meetingPack = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($student)->post(route('admin.meeting-packs.unarchive', $meetingPack));

        $response->assertForbidden();
    }
}
