<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Exceptions\Content\QaThreadInUseException;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use App\UseCases\QaThread\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_thread_without_replies(): void
    {
        $owner = User::factory()->student()->create();
        $thread = QaThread::factory()->create();

        (new DestroyAction)($thread, $owner);

        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_throws_when_replies_exist(): void
    {
        $owner = User::factory()->student()->create();
        $thread = QaThread::factory()->create();
        QaReply::factory()->create(['qa_thread_id' => $thread->id]);

        $this->expectException(QaThreadInUseException::class);

        (new DestroyAction)($thread, $owner);
    }

    public function test_admin_can_delete_thread_with_replies(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();
        $reply = QaReply::factory()->create(['qa_thread_id' => $thread->id]);

        (new DestroyAction)($thread, $admin);

        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
        $this->assertDatabaseMissing('qa_replies', ['id' => $reply->id]);
    }
}
