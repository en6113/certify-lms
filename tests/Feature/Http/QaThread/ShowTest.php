<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Models\Certification;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_in_progress_student_can_view_thread(): void
    {
        $poster = User::factory()->student()->inProgress()->create();
        $viewer = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->for($poster)->create();
        QaReply::factory()->create(['qa_thread_id' => $thread->id, 'body' => 'この回答が見えるはず']);

        $response = $this->actingAs($viewer)->get(route('qa-board.show', $thread));

        $response->assertOk();
        $response->assertViewIs('qa-thread.show');
        $response->assertSee('この回答が見えるはず');
    }

    public function test_assigned_coach_can_view_unassigned_coach_cannot(): void
    {
        $admin = User::factory()->admin()->create();
        $assignedCoach = User::factory()->coach()->create();
        $unassignedCoach = User::factory()->coach()->create();
        $certification = Certification::factory()->published()->create();
        $certification->coaches()->attach($assignedCoach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $thread = QaThread::factory()->for($certification)->create();

        $this->actingAs($assignedCoach)->get(route('qa-board.show', $thread))->assertOk();
        $this->actingAs($unassignedCoach)->get(route('qa-board.show', $thread))->assertForbidden();
    }

    public function test_admin_can_view_via_admin_route(): void
    {
        $admin = User::factory()->admin()->create();
        $thread = QaThread::factory()->create();

        $this->actingAs($admin)->get(route('admin.qa-board.show', $thread))->assertOk();
    }

    public function test_replies_are_ordered_oldest_first(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $thread = QaThread::factory()->create();
        QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'body' => '最初の回答です',
            'created_at' => now()->subMinutes(10),
        ]);
        QaReply::factory()->create([
            'qa_thread_id' => $thread->id,
            'body' => '後から届いた回答です',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('qa-board.show', $thread));

        $response->assertOk();
        $response->assertSeeInOrder(['最初の回答です', '後から届いた回答です']);
    }
}
