<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_archives_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $response = $this->actingAs($admin)->post(route('admin.meeting-packs.archive', $meetingPack));

        $response->assertRedirect(route('admin.meeting-packs.show', $meetingPack));
        $this->assertSame('archived', $meetingPack->fresh()->status->value);
        $this->assertSame($admin->id, $meetingPack->fresh()->updated_by_user_id);
    }

    public function test_cannot_archive_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.meeting-packs.archive', $meetingPack));

        $response->assertStatus(409);
    }

    public function test_cannot_archive_already_archived(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->archived()->create();

        $response = $this->actingAs($admin)->postJson(route('admin.meeting-packs.archive', $meetingPack));

        $response->assertStatus(409);
    }

    public function test_coach_cannot_archive(): void
    {
        $coach = User::factory()->coach()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $response = $this->actingAs($coach)->post(route('admin.meeting-packs.archive', $meetingPack));

        $response->assertForbidden();
    }

    public function test_student_cannot_archive(): void
    {
        $student = User::factory()->student()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $response = $this->actingAs($student)->post(route('admin.meeting-packs.archive', $meetingPack));

        $response->assertForbidden();
    }
}
