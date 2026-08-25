<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanInvalidTransitionException;
use App\Models\Plan;
use App\Models\User;
use App\UseCases\Plan\ArchiveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 受講プランアーカイブ(published → archived) Action の状態遷移ガードを直接検証する。
 * 公開中以外からの呼出は不正遷移として弾かれる。認可(admin のみ)は別途 PlanPolicyTest / Feature\Http 側で検証する。
 */
class ArchiveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_archives_published_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $result = app(ArchiveAction::class)($plan, $admin);

        $this->assertSame(PlanStatus::Archived, $result->status);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Archived->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_throws_when_plan_is_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $this->expectException(PlanInvalidTransitionException::class);

        app(ArchiveAction::class)($plan, $admin);
    }

    public function test_throws_when_plan_is_already_archived(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->archived()->create();

        $this->expectException(PlanInvalidTransitionException::class);

        app(ArchiveAction::class)($plan, $admin);
    }

    public function test_status_is_unchanged_after_invalid_transition(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        try {
            app(ArchiveAction::class)($plan, $admin);
            $this->fail('PlanInvalidTransitionException が throw されるはず');
        } catch (PlanInvalidTransitionException) {
            // 期待通り
        }

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Draft->value,
        ]);
    }
}
