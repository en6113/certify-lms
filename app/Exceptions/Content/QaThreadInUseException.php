<?php

declare(strict_types=1);

namespace App\Exceptions\Content;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * QaThread 削除時、QaReply(質問回答)が紐づいている場合に throw し、削除を拒否する。
 */
final class QaThreadInUseException extends ConflictHttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('この質問は回答が紐付いているため削除できません。', $previous);
    }
}
