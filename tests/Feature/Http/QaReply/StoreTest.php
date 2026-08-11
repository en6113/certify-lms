<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_post_reply(): void
    {
        $poster = User::factory()->student()->inProgress()->create();
        $replier = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($poster)->create();

        $response = $this->actingAs($replier)->post(route('qa-board.replies.store', $thread), [
            'body' => '私も同じ疑問がありました。',
        ]);

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $replier->id,
            'body' => '私も同じ疑問がありました。',
        ]);
    }

    public function test_assigned_coach_can_post_reply(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $certification = Certification::factory()->published()->create();
        $certification->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $thread = QaThread::factory()->for($certification)->create();

        $response = $this->actingAs($coach)->post(route('qa-board.replies.store', $thread), [
            'body' => 'コーチからの回答です。',
        ]);

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseHas('qa_replies', [
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $coach->id,
        ]);
    }

    public function test_unassigned_coach_cannot_post_reply(): void
    {
        $unassignedCoach = User::factory()->coach()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($unassignedCoach)->post(route('qa-board.replies.store', $thread), [
            'body' => '回答',
        ]);

        $response->assertForbidden();
    }

    public function test_admin_cannot_post_reply(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($admin)->post(route('qa-board.replies.store', $thread), [
            'body' => '回答',
        ]);

        $response->assertForbidden();
    }

    public function test_validation_body_required(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($student)->post(route('qa-board.replies.store', $thread), [
            'body' => '',
        ]);

        $response->assertSessionHasErrors('body');
        $this->assertDatabaseCount('qa_replies', 0);
    }
}
