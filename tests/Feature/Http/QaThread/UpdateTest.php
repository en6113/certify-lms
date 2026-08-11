<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_thread(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create(['title' => '旧タイトル']);

        $response = $this->actingAs($owner)->patch(route('qa-board.update', $thread), [
            'title' => '新タイトル',
            'body' => '新本文',
        ]);

        $response->assertRedirect(route('qa-board.index'));
        $this->assertDatabaseHas('qa_threads', ['id' => $thread->id, 'title' => '新タイトル']);
    }

    public function test_other_student_cannot_update(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $other = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $response = $this->actingAs($other)->patch(route('qa-board.update', $thread), [
            'title' => '新タイトル',
            'body' => '新本文',
        ]);

        $response->assertForbidden();
    }

    public function test_validation_title_required(): void
    {
        $owner = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->patch(route('qa-board.update', $thread), [
            'title' => '',
            'body' => '本文',
        ]);

        $response->assertSessionHasErrors('title');
    }
}
