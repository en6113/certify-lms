<?php

declare(strict_types=1);

namespace App\UseCases\QaReply;

use App\Models\QaReply;

/**
 * 質問回答を削除するユースケース。
 */
final class DestroyAction
{
    public function __invoke(QaReply $reply): void
    {
        $reply->delete();
    }
}
