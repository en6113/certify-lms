<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanInvalidTransitionException;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * アーカイブ済の受講プランを下書きへ戻す（archived → draft）ユースケース。
 * アーカイブ済以外の状態からの呼出は PlanInvalidTransitionException（409）。
 */
final class UnarchiveAction
{
    /**
     * @throws PlanInvalidTransitionException アーカイブ済以外からの呼出
     */
    public function __invoke(Plan $plan, User $admin): Plan
    {
        if ($plan->status !== PlanStatus::Archived) {
            throw PlanInvalidTransitionException::forUnarchive();
        }

        return DB::transaction(function () use ($plan, $admin) {
            $plan->update([
                'status' => PlanStatus::Draft->value,
                'updated_by_user_id' => $admin->id,
            ]);

            return $plan->fresh();
        });
    }
}
