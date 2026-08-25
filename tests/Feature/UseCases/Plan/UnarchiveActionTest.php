<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanInvalidTransitionException;
use App\Models\Plan;
use App\Models\User;
use App\UseCases\Plan\UnarchiveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 受講プラン下書き復帰(archived → draft) Action の状態遷移ガードを直接検証する。
 * アーカイブ済以外からの呼出は不正遷移として弾かれる。認可(admin のみ)は別途 PlanPolicyTest / Feature\Http 側で検証する。
 */
class UnarchiveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_unarchives_archived_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->archived()->create();

        $result = app(UnarchiveAction::class)($plan, $admin);

        $this->assertSame(PlanStatus::Draft, $result->status);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Draft->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_throws_when_plan_is_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $this->expectException(PlanInvalidTransitionException::class);

        app(UnarchiveAction::class)($plan, $admin);
    }

    public function test_throws_when_plan_is_published(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $this->expectException(PlanInvalidTransitionException::class);

        app(UnarchiveAction::class)($plan, $admin);
    }

    public function test_status_is_unchanged_after_invalid_transition(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        try {
            app(UnarchiveAction::class)($plan, $admin);
            $this->fail('PlanInvalidTransitionException が throw されるはず');
        } catch (PlanInvalidTransitionException) {
            // 期待通り
        }

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Published->value,
        ]);
    }
}
