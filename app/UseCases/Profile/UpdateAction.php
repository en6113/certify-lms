<?php

declare(strict_types=1);

namespace App\UseCases\Profile;

use App\Enums\UserRole;
use App\Models\User;

/**
 * 本人プロフィール(氏名 / 自己紹介 / 固定面談URL)の更新ユースケース。
 *
 * meeting_url は role がコーチの場合のみ更新対象に含める。
 * 受講生 / 管理者ロールで meeting_url を送ってきても無視する。
 */
final class UpdateAction
{
    /**
     * @param array<string, mixed> $input
     */
    public function __invoke(User $user, array $input): User
    {
        $attributes = [
            'name' => $input['name'],
            'bio' => $input['bio'] ?? null,
        ];

        if ($user->role === UserRole::Coach) {
            $attributes['meeting_url'] = $input['meeting_url'] ?? null;
        }

        $user->update($attributes);

        return $user->refresh();
    }
}
