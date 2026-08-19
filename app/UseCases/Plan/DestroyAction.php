<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Exceptions\Plan\PlanNotDeletableException;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

/**
 * 受講プランを削除するユースケース。下書きかつ受講者未紐づきの場合のみ削除可能(招待・受講中ユーザーとの参照整合性を守るため)。
 */
final class DestroyAction
{
    /**
     * @throws PlanNotDeletableException 下書きかつ受講者未紐づき以外の受講プランは削除不可
     */
    public function __invoke(Plan $plan): void
    {
        if ($plan->status !== PlanStatus::Draft || $plan->users()->exists()) {
            throw new PlanNotDeletableException;
        }

        DB::transaction(fn () => $plan->delete());
    }
}
