<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Exceptions\Content\QaThreadInUseException;
use App\Models\QaReply;
use App\Models\QaThread;
use App\UseCases\QaThread\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_thread_without_replies(): void
    {
        $thread = QaThread::factory()->create();

        (new DestroyAction)($thread);

        $this->assertDatabaseMissing('qa_threads', ['id' => $thread->id]);
    }

    public function test_throws_when_replies_exist(): void
    {
        $thread = QaThread::factory()->create();
        QaReply::factory()->create(['qa_thread_id' => $thread->id]);

        $this->expectException(QaThreadInUseException::class);

        (new DestroyAction)($thread);
    }
}
