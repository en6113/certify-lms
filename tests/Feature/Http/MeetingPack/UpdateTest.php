<?php

declare(strict_types=1);

namespace Tests\Feature\Http\MeetingPack;

use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->draft()->create([
            'name' => '旧パック名',
            'meeting_count' => 2,
            'price' => 5000,
        ]);

        $payload = [
            'name' => '新パック名',
            'description' => '更新後の説明',
            'meeting_count' => 2,
            'price' => 6000,
            'stripe_price_id' => null,
            'sort_order' => 15,
        ];

        $response = $this->actingAs($admin)->put(route('admin.meeting-packs.update', $meetingPack), $payload);

        $response->assertRedirect(route('admin.meeting-packs.show', $meetingPack));
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $meetingPack->id,
            'name' => '新パック名',
            'meeting_count' => 2,
            'price' => 6000,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_status_is_unchanged_even_if_payload_includes_status(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $payload = [
            'name' => $meetingPack->name,
            'meeting_count' => $meetingPack->meeting_count,
            'price' => $meetingPack->price,
            'status' => 'draft',
        ];

        $this->actingAs($admin)->put(route('admin.meeting-packs.update', $meetingPack), $payload);

        $this->assertSame('published', $meetingPack->fresh()->status->value);
    }

    public function test_required_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.meeting-packs.update', $meetingPack), [
                'name' => '',
                'meeting_count' => $meetingPack->meeting_count,
                'price' => $meetingPack->price,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_coach_cannot_update_meeting_pack(): void
    {
        $coach = User::factory()->coach()->create();
        $meetingPack = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($coach)->put(route('admin.meeting-packs.update', $meetingPack), [
            'name' => 'Hack',
            'meeting_count' => $meetingPack->meeting_count,
            'price' => $meetingPack->price,
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_update_meeting_pack(): void
    {
        $student = User::factory()->student()->create();
        $meetingPack = MeetingPack::factory()->draft()->create();

        $response = $this->actingAs($student)->put(route('admin.meeting-packs.update', $meetingPack), [
            'name' => 'Hack',
            'meeting_count' => $meetingPack->meeting_count,
            'price' => $meetingPack->price,
        ]);

        $response->assertForbidden();
    }
}
