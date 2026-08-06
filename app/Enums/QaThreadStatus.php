<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 質問掲示板(QaThread)の状態を表す Enum。2 値モデル。
 *
 * - UnResolved: 未解決(初期値)
 * - Resolved: 解決済(投稿した受講生に限り、切り替え可能)
 */
enum QaThreadStatus: string
{
    case UnResolved = 'unresolved';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::UnResolved => '未解決',
            self::Resolved => '解決済',
        };
    }
}
