<?php

declare(strict_types=1);

namespace App\UseCases\Plan;

use App\Enums\PlanStatus;
use App\Models\Plan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * admin 用の受講プラン一覧をフィルタ付きで取得するユースケース。並び順は sort_order 昇順(Plan::scopeOrdered)。
 * withQueryString()は<x-paginator>側でも呼ばれているため、実質的には保険。
 */
final class IndexAction
{
    public function __invoke(
        ?string $keyword,
        ?PlanStatus $status,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Plan::query()->keyword($keyword)->withCount('users');

        if ($status !== null) {
            $query->where('status', $status->value);
        }

        return $query
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }
}
