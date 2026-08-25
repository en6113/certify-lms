<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanInvalidTransitionException;
use App\Models\Plan;
use App\Models\User;
use App\UseCases\Plan\PublishAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 受講プラン公開(draft → published) Action の状態遷移ガードを直接検証する。
 * 下書き以外からの呼出は不正遷移として弾かれる。認可(admin のみ)は別途 PlanPolicyTest / Feature\Http 側で検証する。
 */
class PublishActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_publishes_draft_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->draft()->create();

        $result = app(PublishAction::class)($plan, $admin);

        $this->assertSame(PlanStatus::Published, $result->status);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Published->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_throws_when_plan_is_already_published(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->published()->create();

        $this->expectException(PlanInvalidTransitionException::class);

        app(PublishAction::class)($plan, $admin);
    }

    public function test_throws_when_plan_is_archived(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->archived()->create();

        $this->expectException(PlanInvalidTransitionException::class);

        app(PublishAction::class)($plan, $admin);
    }

    public function test_status_is_unchanged_after_invalid_transition(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = Plan::factory()->archived()->create();

        try {
            app(PublishAction::class)($plan, $admin);
            $this->fail('PlanInvalidTransitionException が throw されるはず');
        } catch (PlanInvalidTransitionException) {
            // 期待通り
        }

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'status' => PlanStatus::Archived->value,
        ]);
    }
}
