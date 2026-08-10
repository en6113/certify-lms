<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Exceptions\QaThread\QaThreadInvalidStatusTransitionException;
use App\Models\QaThread;

/**
 * 質問スレッドを解決済にするユースケース。
 *
 * - 既に解決済なら `QaThreadInvalidStatusTransitionException::forResolve()`
 */
final class ResolveAction
{
    /**
     * @throws QaThreadInvalidStatusTransitionException
     */
    public function __invoke(QaThread $thread): QaThread
    {
        if ($thread->status === QaThreadStatus::Resolved) {
            throw QaThreadInvalidStatusTransitionException::forResolve();
        }

        $thread->update([
            'status' => QaThreadStatus::Resolved,
            'resolved_at' => now(),
        ]);

        return $thread->fresh();
    }
}
