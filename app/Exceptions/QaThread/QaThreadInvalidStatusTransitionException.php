<?php

declare(strict_types=1);

namespace App\Exceptions\QaThread;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 質問スレッドの解決状態遷移（resolve / unresolve）が不正な開始状態から呼ばれた際の例外（HTTP 409）。
 * バリエーションごとに static factory（`forResolve` / `forUnresolve`）でメッセージを生成する。
 */
final class QaThreadInvalidStatusTransitionException extends ConflictHttpException
{
    public static function forResolve(): self
    {
        return new self('未解決の質問のみ解決済に変更できます。');
    }

    public static function forUnresolve(): self
    {
        return new self('解決済の質問のみ未解決に変更できます。');
    }

    private function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, $previous);
    }
}
