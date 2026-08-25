<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 質問掲示板(QaThread)の状態を表す Enum。2 値モデル。
 *
 * - Open: 未解決(初期値)
 * - Resolved: 解決済(投稿した受講生に限り、切り替え可能)
 */
enum QaThreadStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => '未解決',
            self::Resolved => '解決済',
        };
    }
}
