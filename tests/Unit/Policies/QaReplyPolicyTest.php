<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\Policies\QaReplyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QaReplyPolicyTest extends TestCase
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

    public function test_view_allowed_for_admin_and_any_in_progress_student(): void
    {
        $threadOwner = User::factory()->student()->inProgress()->create();
        $otherStudent = User::factory()->student()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->for($threadOwner)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->view($admin, $thread));
        $this->assertTrue($policy->view($otherStudent, $thread));
    }

    public function test_view_denied_for_graduated_student(): void
    {
        $thread = QaThread::factory()->create();
        $graduated = User::factory()->student()->graduated()->create();

        $this->assertFalse((new QaReplyPolicy)->view($graduated, $thread));
    }

    public function test_view_allowed_for_assigned_coach_denied_for_unassigned_coach(): void
    {
        $admin = User::factory()->admin()->create();
        $assignedCoach = User::factory()->coach()->create();
        $unassignedCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->published()->create();
        $this->attachCoach($certification, $assignedCoach, $admin);
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->view($assignedCoach, $thread->fresh()));
        $this->assertFalse($policy->view($unassignedCoach, $thread->fresh()));
    }

    public function test_create_allowed_for_in_progress_student_and_assigned_coach(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->inProgress()->create();
        $assignedCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->published()->create();
        $this->attachCoach($certification, $assignedCoach, $admin);
        $thread = QaThread::factory()->for($certification)->create();

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->create($student, $thread->fresh()));
        $this->assertTrue($policy->create($assignedCoach, $thread->fresh()));
    }

    public function test_create_denied_for_graduated_student_unassigned_coach_and_admin(): void
    {
        $graduated = User::factory()->student()->graduated()->create();
        $unassignedCoach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();

        $policy = new QaReplyPolicy;
        $this->assertFalse($policy->create($graduated, $thread));
        $this->assertFalse($policy->create($unassignedCoach, $thread->fresh()));
        $this->assertFalse($policy->create($admin, $thread));
    }

    public function test_update_allowed_only_for_reply_author(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $reply = QaReply::factory()->create(['reply_user_id' => $author->id]);

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->update($author, $reply));
        $this->assertFalse($policy->update($other, $reply));
    }

    public function test_delete_allowed_for_author_and_admin_denied_for_others(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $admin = User::factory()->admin()->create();
        $reply = QaReply::factory()->create(['reply_user_id' => $author->id]);

        $policy = new QaReplyPolicy;
        $this->assertTrue($policy->delete($author, $reply));
        $this->assertTrue($policy->delete($admin, $reply));
        $this->assertFalse($policy->delete($other, $reply));
    }
}
