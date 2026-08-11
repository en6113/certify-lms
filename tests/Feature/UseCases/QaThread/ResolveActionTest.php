<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Exceptions\QaThread\QaThreadInvalidStatusTransitionException;
use App\Models\QaThread;
use App\UseCases\QaThread\ResolveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_unresolved_thread(): void
    {
        $thread = QaThread::factory()->create([
            'status' => QaThreadStatus::UnResolved->value,
            'resolved_at' => null,
        ]);

        $result = (new ResolveAction)($thread);

        $this->assertSame(QaThreadStatus::Resolved, $result->status);
        $this->assertNotNull($result->resolved_at);
    }

    public function test_throws_when_already_resolved(): void
    {
        $thread = QaThread::factory()->create([
            'status' => QaThreadStatus::Resolved->value,
            'resolved_at' => now(),
        ]);

        $this->expectException(QaThreadInvalidStatusTransitionException::class);

        (new ResolveAction)($thread);
    }
}
