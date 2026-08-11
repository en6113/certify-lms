<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Exceptions\QaThread\QaThreadInvalidStatusTransitionException;
use App\Models\QaThread;
use App\UseCases\QaThread\UnresolveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnresolveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unresolves_resolved_thread(): void
    {
        $thread = QaThread::factory()->create([
            'status' => QaThreadStatus::Resolved->value,
            'resolved_at' => now(),
        ]);

        $result = (new UnresolveAction)($thread);

        $this->assertSame(QaThreadStatus::UnResolved, $result->status);
        $this->assertNull($result->resolved_at);
    }

    public function test_throws_when_already_unresolved(): void
    {
        $thread = QaThread::factory()->create([
            'status' => QaThreadStatus::UnResolved->value,
            'resolved_at' => null,
        ]);

        $this->expectException(QaThreadInvalidStatusTransitionException::class);

        (new UnresolveAction)($thread);
    }
}
