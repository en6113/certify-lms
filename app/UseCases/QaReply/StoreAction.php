<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;

/**
 * QaReply(質問回答)を新規作成するユースケース。
 */
final class StoreAction
{
    /**
     * @param array{reply_user_id: string, qa_thread_id: string, body: ?string} $validated
     */
    public function __invoke(array $validated): QaReply
    {
        return QaReply::create($validated);
    }
}
