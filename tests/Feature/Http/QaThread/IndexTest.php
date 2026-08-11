<?php

declare(strict_types=1);

namespace Tests\Feature\Http\QaThread;

use App\Enums\QaThreadStatus;
use App\Models\Certification;
use App\Models\QaThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_sees_only_published_certification_threads(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $publishedCert = Certification::factory()->published()->create();
        $draftCert = Certification::factory()->draft()->create();
        QaThread::factory()->for($publishedCert)->create(['title' => '公開資格の質問']);
        QaThread::factory()->for($draftCert)->create(['title' => '下書き資格の質問']);

        $response = $this->actingAs($student)->get(route('qa-board.index'));

        $response->assertOk();
        $response->assertSee('公開資格の質問');
        $response->assertDontSee('下書き資格の質問');
    }

    public function test_coach_sees_only_threads_for_assigned_certifications(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->coach()->create();
        $assignedCert = Certification::factory()->published()->create();
        $otherCert = Certification::factory()->published()->create();
        $assignedCert->coaches()->attach($coach->id, [
            'id' => (string) Str::ulid(),
            'assigned_by_user_id' => $admin->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        QaThread::factory()->for($assignedCert)->create(['title' => '担当資格の質問']);
        QaThread::factory()->for($otherCert)->create(['title' => '担当外資格の質問']);

        $response = $this->actingAs($coach)->get(route('qa-board.index'));

        $response->assertOk();
        $response->assertSee('担当資格の質問');
        $response->assertDontSee('担当外資格の質問');
    }

    public function test_admin_sees_all_threads_via_admin_route(): void
    {
        $admin = User::factory()->admin()->create();
        $draftCert = Certification::factory()->draft()->create();
        QaThread::factory()->for($draftCert)->create(['title' => '下書き資格の質問(管理者は見える)']);

        $response = $this->actingAs($admin)->get(route('admin.qa-board.index'));

        $response->assertOk();
        $response->assertSee('下書き資格の質問(管理者は見える)');
    }

    public function test_graduated_student_is_forbidden(): void
    {
        $graduated = User::factory()->student()->graduated()->create();

        $this->actingAs($graduated)
            ->get(route('qa-board.index'))
            ->assertForbidden();
    }

    public function test_certification_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $targetCert = Certification::factory()->published()->create();
        $otherCert = Certification::factory()->published()->create();
        QaThread::factory()->for($targetCert)->create(['title' => '対象資格の質問']);
        QaThread::factory()->for($otherCert)->create(['title' => '他資格の質問']);

        $response = $this->actingAs($admin)->get(route('admin.qa-board.index', ['certification_id' => $targetCert->id]));

        $response->assertOk();
        $response->assertSee('対象資格の質問');
        $response->assertDontSee('他資格の質問');
    }

    public function test_status_filter(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $cert = Certification::factory()->published()->create();
        QaThread::factory()->for($cert)->create(['title' => '解決済の質問', 'status' => QaThreadStatus::Resolved->value]);
        QaThread::factory()->for($cert)->create(['title' => '未解決の質問', 'status' => QaThreadStatus::UnResolved->value]);

        $response = $this->actingAs($student)->get(route('qa-board.index', ['status' => QaThreadStatus::Resolved->value]));

        $response->assertOk();
        $response->assertSee('解決済の質問');
        $response->assertDontSee('未解決の質問');
    }

    public function test_keyword_filter_matches_body(): void
    {
        $student = User::factory()->student()->inProgress()->create();
        $cert = Certification::factory()->published()->create();
        QaThread::factory()->for($cert)->create(['title' => '質問A', 'body' => 'サブネットマスクの計算方法が分かりません']);
        QaThread::factory()->for($cert)->create(['title' => '質問B', 'body' => 'IAMロールの権限設定について']);

        $response = $this->actingAs($student)->get(route('qa-board.index', ['keyword' => 'サブネット']));

        $response->assertOk();
        $response->assertSee('質問A');
        $response->assertDontSee('質問B');
    }

    public function test_orders_threads_newest_first(): void
    {
        $admin = User::factory()->admin()->create();
        $older = QaThread::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = QaThread::factory()->create(['created_at' => now()->subDay()]);
        $newest = QaThread::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($admin)->get(route('admin.qa-board.index'));

        $threads = $response->viewData('threads');
        $this->assertSame([$newest->id, $newer->id, $older->id], $threads->pluck('id')->all());
    }

    public function test_paginates_15_per_page(): void
    {
        $admin = User::factory()->admin()->create();
        QaThread::factory()->count(17)->create();

        $response = $this->actingAs($admin)->get(route('admin.qa-board.index'));

        $threads = $response->viewData('threads');
        $this->assertSame(15, $threads->perPage());
        $this->assertSame(17, $threads->total());
    }
}
