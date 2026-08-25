<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_delete_own_reply(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $author->id,
        ]);

        $response = $this->actingAs($author)->delete(
            route('qa-board.replies.destroy', ['thread' => $thread, 'reply' => $reply])
        );

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_admin_can_delete_via_admin_route(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->create(['qa_thread_id' => $thread->id]);

        $response = $this->actingAs($admin)->delete(
            route('admin.qa-board.replies.destroy', ['thread' => $thread, 'reply' => $reply])
        );

        $response->assertRedirect(route('admin.qa-board.show', $thread));
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }

    public function test_other_student_cannot_delete(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $author->id,
        ]);

        $this->actingAs($other)
            ->delete(route('qa-board.replies.destroy', ['thread' => $thread, 'reply' => $reply]))
            ->assertForbidden();
    }
}
