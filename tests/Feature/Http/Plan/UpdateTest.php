<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create([
            'name' => '旧プラン名',
            'duration_days' => 90,
            'default_meeting_quota' => 12,
        ]);

        $payload = [
            'name' => '新プラン名',
            'description' => '更新後の説明',
            'duration_days' => 180,
            'default_meeting_quota' => 24,
            'sort_order' => 5,
        ];

        $response = $this->actingAs($admin)->put(route('admin.plans.update', $plan), $payload);

        $response->assertRedirect(route('admin.plans.show', $plan));
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => '新プラン名',
            'duration_days' => 180,
            'default_meeting_quota' => 24,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_status_is_unchanged_even_if_payload_includes_status(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $payload = [
            'name' => $plan->name,
            'duration_days' => $plan->duration_days,
            'default_meeting_quota' => $plan->default_meeting_quota,
            'status' => 'draft',
        ];

        $this->actingAs($admin)->put(route('admin.plans.update', $plan), $payload);

        $this->assertSame('published', $plan->fresh()->status->value);
    }

    public function test_required_fields_are_validated(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.plans.update', $plan), [
                'name' => '',
                'duration_days' => $plan->duration_days,
                'default_meeting_quota' => $plan->default_meeting_quota,
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_coach_cannot_update_plan(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->draft()->create();

        $response = $this->actingAs($coach)->put(route('admin.plans.update', $plan), [
            'name' => 'Hack',
            'duration_days' => $plan->duration_days,
            'default_meeting_quota' => $plan->default_meeting_quota,
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_update_plan(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->draft()->create();

        $response = $this->actingAs($student)->put(route('admin.plans.update', $plan), [
            'name' => 'Hack',
            'duration_days' => $plan->duration_days,
            'default_meeting_quota' => $plan->default_meeting_quota,
        ]);

        $response->assertForbidden();
    }
}
