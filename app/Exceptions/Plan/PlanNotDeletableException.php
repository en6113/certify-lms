<?php

declare(strict_types=1);

namespace App\Exceptions\Plan;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 削除条件を満たさない受講プランを削除しようとした際の例外（HTTP 409）。
 * `Plan\DestroyAction` で下書きかつ受講者未紐づきの面談パック以外を削除しようとした場合にthrowする。
 */
final class PlanNotDeletableException extends ConflictHttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('下書きかつ受講者未紐づきの場合のみ削除できます。', $previous);
    }
}
