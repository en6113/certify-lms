<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;

/**
 * QaThread(質問スレッド)を新規作成するユースケース。
 */
final class StoreAction
{
    /**
     * @param array{certification_id: string, title: string, body: ?string, user_id: string} $validated
     */
    public function __invoke(array $validated): QaThread
    {
        return QaThread::create($validated);
    }
}
