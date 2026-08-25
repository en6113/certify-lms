<?php

declare(strict_types=1);

namespace App\Exceptions\Avatar;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * アイコン画像(Avatar)のストレージ操作（保存 / 削除)が失敗した場合に throw される。
 * トランザクション ROLLBACK と組み合わせて、DB と Storage の不整合(orphan ファイル / orphan レコード)を防ぐ。
 */
final class AvatarStorageException extends HttpException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(500, 'アイコン画像の保存に失敗しました。時間をおいて再度お試しください。', $previous);
    }
}
