<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Exceptions\Content\QaThreadInUseException;
use App\Models\QaThread;

/**
 * 質問スレッドを削除するユースケース。
 *
 * 共有マスタの削除ガードとして、回答（QaReply）が1 件でも紐づいていれば QaThreadInUseException(409)
 * を throw して削除を拒否する。
 */
final class DestroyAction
{
    /**
     * @throws QaThreadInUseException
     */
    public function __invoke(QaThread $thread): void
    {
        if ($thread->replies()->exists()) {
            throw new QaThreadInUseException;
        }

        $thread->delete();
    }
}
