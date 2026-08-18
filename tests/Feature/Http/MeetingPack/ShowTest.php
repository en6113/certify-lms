<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_meeting_pack_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.show', $meetingPack));

        $response->assertOk();
        $response->assertViewIs('meeting-pack.management.show');
        $response->assertViewHas('plan');
    }

    public function test_created_by_and_updated_by_are_eager_loaded(): void
    {
        $creator = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->create([
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $creator->id,
        ]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.meeting-packs.show', $meetingPack));

        $plan = $response->viewData('plan');
        $this->assertTrue($plan->relationLoaded('createdBy'));
        $this->assertTrue($plan->relationLoaded('updatedBy'));
        $this->assertSame($creator->id, $plan->createdBy->id);
    }

    public function test_coach_cannot_view_meeting_pack_detail(): void
    {
        $coach = User::factory()->coach()->create();
        $meetingPack = MeetingPack::factory()->create();

        $this->actingAs($coach)
            ->get(route('admin.meeting-packs.show', $meetingPack))
            ->assertForbidden();
    }

    public function test_student_cannot_view_meeting_pack_detail(): void
    {
        $student = User::factory()->student()->create();
        $meetingPack = MeetingPack::factory()->create();

        $this->actingAs($student)
            ->get(route('admin.meeting-packs.show', $meetingPack))
            ->assertForbidden();
    }
}
