<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use App\Policies\QaThreadPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QaThreadPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function attachCoach(Certification $certification, User $coach, User $admin): void
    {
        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_view_any_allowed_for_admin_coach_and_in_progress_student(): void
    {
        $policy = new QaThreadPolicy;

        $this->assertTrue($policy->viewAny(User::factory()->admin()->create()));
        $this->assertTrue($policy->viewAny(User::factory()->coach()->create()));
        $this->assertTrue($policy->viewAny(User::factory()->student()->inProgress()->create()));
    }

    public function test_view_any_denied_for_graduated_student(): void
    {
        $graduated = User::factory()->student()->graduated()->create();

        $this->assertFalse((new QaThreadPolicy)->viewAny($graduated));
    }

    public function test_view_allowed_for_admin_and_any_in_progress_student(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->view($admin, $thread));
        $this->assertTrue($policy->view($otherStudent, $thread), '受講中の受講生は他人のスレッドも閲覧できる');
    }

    public function test_view_denied_for_graduated_student(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $graduated = User::factory()->student()->graduated()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $this->assertFalse((new QaThreadPolicy)->view($graduated, $thread));
    }

    public function test_view_allowed_for_assigned_coach_denied_for_unassigned_coach(): void
    {
        $admin = User::factory()->admin()->create();
        $assignedCoach = User::factory()->coach()->create();
        $unassignedCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->published()->create();
        $this->attachCoach($certification, $assignedCoach, $admin);
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->view($assignedCoach, $thread->fresh()));
        $this->assertFalse($policy->view($unassignedCoach, $thread->fresh()));
    }

    public function test_create_allowed_only_for_in_progress_student(): void
    {
        $policy = new QaThreadPolicy;

        $this->assertTrue($policy->create(User::factory()->student()->inProgress()->create()));
        $this->assertFalse($policy->create(User::factory()->student()->graduated()->create()));
        $this->assertFalse($policy->create(User::factory()->coach()->create()));
        $this->assertFalse($policy->create(User::factory()->admin()->create()));
    }

    public function test_update_allowed_only_for_owner(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->update($owner, $thread));
        $this->assertFalse($policy->update($other, $thread));
    }

    public function test_update_denied_when_owner_is_graduated(): void
    {
        $graduatedOwner = User::factory()->student()->graduated()->create();
        $thread = QaThread::factory()->for($graduatedOwner)->create();

        $this->assertFalse((new QaThreadPolicy)->update($graduatedOwner, $thread));
    }

    public function test_delete_allowed_for_owner_and_admin_denied_for_others(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->delete($owner, $thread));
        $this->assertTrue($policy->delete($admin, $thread));
        $this->assertFalse($policy->delete($other, $thread));
    }

    public function test_resolve_and_unresolve_allowed_only_for_owner(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $policy = new QaThreadPolicy;
        $this->assertTrue($policy->resolve($owner, $thread));
        $this->assertFalse($policy->resolve($other, $thread));
        $this->assertTrue($policy->unresolve($owner, $thread));
        $this->assertFalse($policy->unresolve($other, $thread));
    }
}
