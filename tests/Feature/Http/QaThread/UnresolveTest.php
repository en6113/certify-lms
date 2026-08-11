<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnresolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_unresolve_thread(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create([
            'status' => QaThreadStatus::Resolved->value,
            'resolved_at' => now(),
        ]);

        $response = $this->actingAs($owner)->post(route('qa-board.unresolve', $thread));

        $response->assertRedirect(route('qa-board.show', $thread));
        $this->assertSame(QaThreadStatus::UnResolved, $thread->fresh()->status);
    }

    public function test_other_student_cannot_unresolve(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create([
            'status' => QaThreadStatus::Resolved->value,
            'resolved_at' => now(),
        ]);

        $this->actingAs($other)->post(route('qa-board.unresolve', $thread))->assertForbidden();
    }

    public function test_returns_409_when_already_unresolved(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create([
            'status' => QaThreadStatus::UnResolved->value,
            'resolved_at' => null,
        ]);

        $response = $this->actingAs($owner)->postJson(route('qa-board.unresolve', $thread));

        $response->assertStatus(409);
    }
}
