<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_delete_own_note(): void
    {
        $author = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->learning()->create();
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->forAuthor($author)->create();

        $response = $this->actingAs($author)->delete(route('enrollment-notes.destroy', $note));

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseMissing('enrollment_notes', ['id' => $note->id]);
    }

    public function test_admin_can_delete_any_note(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $note = EnrollmentNote::factory()->forAuthor($coach)->create();

        $response = $this->actingAs($admin)->delete(route('enrollment-notes.destroy', $note));

        $response->assertRedirect();
        $this->assertDatabaseMissing('enrollment_notes', ['id' => $note->id]);
    }

    public function test_other_coach_cannot_delete(): void
    {
        $author = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $note = EnrollmentNote::factory()->forAuthor($author)->create();

        $response = $this->actingAs($otherCoach)->delete(route('enrollment-notes.destroy', $note));

        $response->assertForbidden();
        $this->assertDatabaseHas('enrollment_notes', ['id' => $note->id]);
    }
}
