<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 受講プランの状態遷移（publish / archive / unarchive）が不正な開始状態から呼ばれた際の例外（HTTP 409）。
 * バリエーションごとに static factory（`forPublish` / `forArchive` / `forUnarchive`）でメッセージを生成する。
 */
final class PlanInvalidTransitionException extends ConflictHttpException
{
    public static function forPublish(): self
    {
        return new self('下書き状態の受講プランのみ公開できます。');
    }

    public static function forArchive(): self
    {
        return new self('公開中の受講プランのみアーカイブできます。');
    }

    public static function forUnarchive(): self
    {
        return new self('アーカイブ済の受講プランのみ下書きへ戻せます。');
    }

    private function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
