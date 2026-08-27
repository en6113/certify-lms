<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Certification;
use App\Models\CertificationCoachAssignment;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use App\Policies\EnrollmentNotePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * EnrollmentNotePolicy の判定を検証する Unit テスト。
 * viewAny/create: 担当コーチ / admin のみ許可。
 * update/delete: 作成者本人 / admin のみ許可。
 */
class EnrollmentNotePolicyTest extends TestCase
{
    use RefreshDatabase;

    private function assignCoach(Certification $certification, User $coach, User $admin): void
    {
        CertificationCoachAssignment::create([
            'id' => (string) Str::ulid(),
            'certification_id' => $certification->id,
            'user_id' => $coach->id,
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
        ]);
    }

    public function test_view_any_allowed_for_admin_and_assigned_coach_denied_for_unassigned_coach_and_student(): void
    {
        $admin = User::factory()->admin()->create();
        $assignedCoach = User::factory()->coach()->create();
        $unassignedCoach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $certification = Certification::factory()->published()->create();
        $this->assignCoach($certification, $assignedCoach, $admin);
        $enrollment = Enrollment::factory()->for($certification)->learning()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->viewAny($admin, $enrollment));
        $this->assertTrue($policy->viewAny($assignedCoach, $enrollment));
        $this->assertFalse($policy->viewAny($unassignedCoach, $enrollment));
        $this->assertFalse($policy->viewAny($student, $enrollment));
    }

    public function test_create_allowed_for_admin_and_assigned_coach_denied_for_unassigned_coach_and_student(): void
    {
        $admin = User::factory()->admin()->create();
        $assignedCoach = User::factory()->coach()->create();
        $unassignedCoach = User::factory()->coach()->create();
        $student = User::factory()->student()->create();
        $certification = Certification::factory()->published()->create();
        $this->assignCoach($certification, $assignedCoach, $admin);
        $enrollment = Enrollment::factory()->for($certification)->learning()->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->create($admin, $enrollment));
        $this->assertTrue($policy->create($assignedCoach, $enrollment));
        $this->assertFalse($policy->create($unassignedCoach, $enrollment));
        $this->assertFalse($policy->create($student, $enrollment));
    }

    public function test_update_allowed_for_author_and_admin_denied_for_other_coach(): void
    {
        $author = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->forAuthor($author)->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->update($author, $note));
        $this->assertTrue($policy->update($admin, $note));
        $this->assertFalse($policy->update($otherCoach, $note));
    }

    public function test_delete_allowed_for_author_and_admin_denied_for_other_coach(): void
    {
        $author = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $admin = User::factory()->admin()->create();
        $note = EnrollmentNote::factory()->forAuthor($author)->create();

        $policy = new EnrollmentNotePolicy;

        $this->assertTrue($policy->delete($author, $note));
        $this->assertTrue($policy->delete($admin, $note));
        $this->assertFalse($policy->delete($otherCoach, $note));
    }
}
