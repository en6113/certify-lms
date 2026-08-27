<?php

declare(strict_types=1);

namespace Tests\Feature\Http\EnrollmentNote;

use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_update_own_note(): void
    {
        $author = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->learning()->create();
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->forAuthor($author)->create([
            'body' => '旧本文',
        ]);

        $response = $this->actingAs($author)->patch(route('enrollment-notes.update', $note), [
            'body' => '新本文',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollment_notes', ['id' => $note->id, 'body' => '新本文']);
    }

    public function test_admin_can_update_any_note(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->learning()->create();
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->forAuthor($coach)->create();

        $response = $this->actingAs($admin)->patch(route('enrollment-notes.update', $note), [
            'body' => '管理者による修正',
        ]);

        $response->assertRedirect(route('enrollments.show', $enrollment));
        $this->assertDatabaseHas('enrollment_notes', ['id' => $note->id, 'body' => '管理者による修正']);
    }

    public function test_other_coach_cannot_update(): void
    {
        $author = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();
        $enrollment = Enrollment::factory()->learning()->create();
        $note = EnrollmentNote::factory()->forEnrollment($enrollment)->forAuthor($author)->create([
            'body' => '旧本文',
        ]);

        $response = $this->actingAs($otherCoach)->patch(route('enrollment-notes.update', $note), [
            'body' => '書き換え',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('enrollment_notes', ['id' => $note->id, 'body' => '旧本文']);
    }

    public function test_validation_body_required(): void
    {
        $author = User::factory()->coach()->create();
        $note = EnrollmentNote::factory()->forAuthor($author)->create();

        $response = $this->actingAs($author)->patch(route('enrollment-notes.update', $note), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
    }
}
