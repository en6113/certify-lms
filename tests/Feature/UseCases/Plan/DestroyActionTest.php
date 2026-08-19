<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Plan;

use App\Exceptions\Plan\PlanNotDeletableException;
use App\Models\Plan;
use App\Models\User;
use App\UseCases\Plan\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 受講プラン削除 Action のガード処理を直接検証する。
 * 「下書き かつ 受講者未紐づき」の両方を満たす場合のみ削除できるという複合条件が本体。
 * 認可(admin のみ)は別途 PlanPolicyTest / Feature\Http 側で検証する。
 */
class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_deletes_draft_plan_without_users(): void
    {
        $plan = Plan::factory()->draft()->create();

        app(DestroyAction::class)($plan);

        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_throws_when_plan_is_published(): void
    {
        $plan = Plan::factory()->published()->create();

        $this->expectException(PlanNotDeletableException::class);

        app(DestroyAction::class)($plan);
    }

    public function test_throws_when_plan_is_archived(): void
    {
        $plan = Plan::factory()->archived()->create();

        $this->expectException(PlanNotDeletableException::class);

        app(DestroyAction::class)($plan);
    }

    public function test_throws_when_draft_plan_has_linked_users(): void
    {
        $plan = Plan::factory()->draft()->create();
        User::factory()->student()->create(['plan_id' => $plan->id]);

        $this->expectException(PlanNotDeletableException::class);

        app(DestroyAction::class)($plan);
    }

    public function test_plan_is_not_deleted_after_exception(): void
    {
        $plan = Plan::factory()->draft()->create();
        User::factory()->student()->create(['plan_id' => $plan->id]);

        try {
            app(DestroyAction::class)($plan);
            $this->fail('PlanNotDeletableException が throw されるはず');
        } catch (PlanNotDeletableException) {
            // 期待通り
        }

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }
}
