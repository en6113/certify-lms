<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * QaThread(質問スレッド)一覧を、ロールによって表示する資格を制御し、紐づく回答件数付きで返すユースケース。
 */
final class IndexAction
{
    public function __invoke(
        User $viewer,
        ?string $status,
        ?string $certificationId,
        ?string $keyword
    ): LengthAwarePaginator {
        return QaThread::with('certification', 'user')
            ->withCount('replies')
            ->forUser($viewer)
            ->status($status)
            ->certification($certificationId)
            ->keyword($keyword)
            ->latest()
            ->paginate(15);
    }
}
