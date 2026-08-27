<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Certification;
use App\Models\CertificationCoachAssignment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreTest extends TestCase
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

    public function test_assigned_coach_can_add_note(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->published()->create();
        $this->assignCoach($certification, $coach, $admin);
        $enrollment = Enrollment::factory()->for($certification)->learning()->create();

        $response = $this->actingAs($coach)->post(route('enrollments.notes.store', $enrollment), [
            'body' => 'チャットの返信が遅れがちです。次回面談で確認します。',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $coach->id,
            'body' => 'チャットの返信が遅れがちです。次回面談で確認します。',
        ]);
    }

    public function test_admin_can_add_note(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->learning()->create();

        $response = $this->actingAs($admin)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '運営側からの観察メモです。',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollment_notes', [
            'enrollment_id' => $enrollment->id,
            'created_by_user_id' => $admin->id,
        ]);
    }

    public function test_unassigned_coach_cannot_add_note(): void
    {
        $unassignedCoach = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->learning()->create();

        $response = $this->actingAs($unassignedCoach)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '担当外からのメモ',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('enrollment_notes', 0);
    }

    public function test_student_cannot_add_note(): void
    {
        $student = User::factory()->student()->create();
        $enrollment = Enrollment::factory()->for($student)->learning()->create();

        $response = $this->actingAs($student)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '受講生からのメモ',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('enrollment_notes', 0);
    }

    public function test_validation_body_required(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = Enrollment::factory()->learning()->create();

        $response = $this->actingAs($admin)->post(route('enrollments.notes.store', $enrollment), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('enrollment_notes', 0);
    }
}
