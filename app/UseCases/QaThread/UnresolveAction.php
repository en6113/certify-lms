<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\QaThreadStatus;
use App\Exceptions\QaThread\QaThreadInvalidStatusTransitionException;
use App\Models\QaThread;

/**
 * 質問スレッドを未解決に戻すユースケース。
 *
 * - 既に未解決なら `QaThreadInvalidStatusTransitionException::forUnresolved()`
 */
final class UnresolveAction
{
    /**
     * @throws QaThreadInvalidStatusTransitionException
     */
    public function __invoke(QaThread $thread): QaThread
    {
        if ($thread->status === QaThreadStatus::UnResolved) {
            throw QaThreadInvalidStatusTransitionException::forUnresolved();
        }

        $thread->update([
            'status' => QaThreadStatus::UnResolved,
            'resolved_at' => null,
        ]);

        return $thread->fresh();
    }
}
