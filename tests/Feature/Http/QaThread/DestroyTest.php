<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_thread(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->delete(route('qa-board.destroy', $thread));

        $response->assertRedirect(route('qa-board.index'));
        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_admin_can_delete_via_admin_route(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.qa-board.destroy', $thread));

        $response->assertRedirect(route('admin.qa-board.index'));
        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_other_student_cannot_delete(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $this->actingAs($other)->delete(route('qa-board.destroy', $thread))->assertForbidden();
    }

    public function test_returns_409_when_replies_exist(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create();
        QaReply::factory()->create(['qa_thread_id' => $thread->id]);

        $response = $this->actingAs($owner)->deleteJson(route('qa-board.destroy', $thread));

        $response->assertStatus(409);
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id]);
    }
}
