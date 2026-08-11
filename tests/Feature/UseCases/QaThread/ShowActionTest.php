<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaThread\ShowAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ShowAction の責務: replies を古い順 + 各replyのuserをまとめてeager loadする。
 */
class ShowActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_loads_replies_ordered_oldest_first_with_user(): void
    {
        $thread = QaThread::factory()->create();
        $firstAuthor = User::factory()->student()->create();
        $secondAuthor = User::factory()->coach()->create();

        $firstReply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $firstAuthor->id,
            'created_at' => now()->subMinutes(10),
        ]);
        $secondReply = QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'reply_user_id' => $secondAuthor->id,
            'created_at' => now(),
        ]);

        $result = (new ShowAction)($thread);

        $this->assertTrue($result->relationLoaded('replies'));
        $this->assertSame([$firstReply->id, $secondReply->id], $result->replies->pluck('id')->all());
        $this->assertTrue($result->replies->first()->relationLoaded('user'));
    }
}
