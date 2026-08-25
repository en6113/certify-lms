<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_plan_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.show', $plan));

        $response->assertOk();
        $response->assertViewIs('plan.management.show');
        $response->assertViewHas('plan');
    }

    public function test_users_created_by_and_updated_by_are_eager_loaded(): void
    {
        $creator = User::factory()->admin()->create();
        $plan = Plan::factory()->create([
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $creator->id,
        ]);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.plans.show', $plan));

        $result = $response->viewData('plan');
        $this->assertTrue($result->relationLoaded('users'));
        $this->assertTrue($result->relationLoaded('createdBy'));
        $this->assertTrue($result->relationLoaded('updatedBy'));
        $this->assertSame($creator->id, $result->createdBy->id);
    }

    public function test_linked_users_are_displayed(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();
        $student = User::factory()->student()->create([
            'name' => '紐づく受講生',
            'email' => 'linked-student@certify-lms.test',
            'plan_id' => $plan->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.plans.show', $plan));

        $response->assertOk();
        $response->assertSee('紐づく受講生');
        $response->assertSee('linked-student@certify-lms.test');
    }

    public function test_coach_cannot_view_plan_detail(): void
    {
        $coach = User::factory()->coach()->create();
        $plan = Plan::factory()->create();

        $this->actingAs($coach)
            ->get(route('admin.plans.show', $plan))
            ->assertForbidden();
    }

    public function test_student_cannot_view_plan_detail(): void
    {
        $student = User::factory()->student()->create();
        $plan = Plan::factory()->create();

        $this->actingAs($student)
            ->get(route('admin.plans.show', $plan))
            ->assertForbidden();
    }
}
