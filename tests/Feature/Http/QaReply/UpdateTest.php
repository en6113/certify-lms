<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaReply;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_update_own_reply(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $author->id,
            'body' => '旧本文',
        ]);

        $response = $this->actingAs($author)->patch(
            route('qa-board.replies.update', ['thread' => $thread, 'reply' => $reply]),
            ['body' => '新本文']
        );

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertDatabaseHas('qa_replies', ['id' => $reply->id, 'body' => '新本文']);
    }

    public function test_other_user_cannot_update(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $author->id,
        ]);

        $response = $this->actingAs($other)->patch(
            route('qa-board.replies.update', ['thread' => $thread, 'reply' => $reply]),
            ['body' => '新本文']
        );

        $response->assertForbidden();
    }

    public function test_validation_body_required(): void
    {
        $author = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $author->id,
        ]);

        $response = $this->actingAs($author)->patch(
            route('qa-board.replies.update', ['thread' => $thread, 'reply' => $reply]),
            ['body' => '']
        );

        $response->assertSessionHasErrors('body');
    }
}
