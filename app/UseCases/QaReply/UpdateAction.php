<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;

/**
 * QaReply(質問回答）を更新するユースケース。
 */
final class UpdateAction
{
    /**
     * @param array{body: string} $validated
     */
    public function __invoke(QaReply $reply, array $validated): QaReply
    {
        $reply->update($validated);

        return $reply->fresh();
    }
}
