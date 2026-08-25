<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Enums\UserRole;
use App\Exceptions\Content\QaThreadInUseException;
use App\Models\QaThread;
use App\Models\User;

/**
 * 質問スレッドを削除するユースケース。
 *
 * 投稿者本人による削除は、回答（QaReply）が1件でも紐づいていれば QaThreadInUseException(409)
 * を throw して拒否する。管理者によるモデレーション削除はこのガードの対象外とし、
 * 紐づく回答ごと削除できる（qa_replies.qa_thread_id の cascadeOnDelete に委ねる）。
 */
final class DestroyAction
{
    /**
     * @throws QaThreadInUseException
     */
    public function __invoke(QaThread $thread, User $viewer): void
    {
        if ($viewer->role !== UserRole::Admin && $thread->replies()->exists()) {
            throw new QaThreadInUseException;
        }

        $thread->delete();
    }
}
