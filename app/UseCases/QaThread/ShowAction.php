<?php

declare(strict_types=1);

namespace App\UseCases\QaThread;

use App\Models\QaThread;

/**
 * QaThread(質問スレッド)詳細を取得するユースケース。回答とユーザーを併記する。
 */
final class ShowAction
{
    public function __invoke(QaThread $thread): QaThread
    {
        return $thread->load(['replies' => fn ($query) => $query->oldest()->with('user')]);
    }
}
