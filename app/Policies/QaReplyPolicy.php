<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * QaReply(質問回答)リソースに対する認可ポリシー。
 *
 * - view: 受講中の受講生 / 当該資格の担当コーチ / admin
 * - create: 受講中の受講生 / 当該資格の担当コーチのみ
 * - update: 投稿者本人のみ
 * - delete: 投稿者本人 / 管理者
 */
class QaReplyPolicy
{
    public function view(User $user, QaThread $thread): bool
    {
        return match (true) {
            $user->role === UserRole::Admin => true,
            $user->role === UserRole::Student => $user->status === UserStatus::InProgress,
            $user->role === UserRole::Coach => $this->isAssignedCoach($thread, $user),
            default => false,
        };
    }

    public function create(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Student
            && $user->status === UserStatus::InProgress
            || $user->role === UserRole::Coach
            && $this->isAssignedCoach($thread, $user);
    }

    public function update(User $user, QaReply $reply): bool
    {
        return $reply->user_id === $user->id;
    }

    public function delete(User $user, QaReply $reply): bool
    {
        return $reply->user_id === $user->id
            || $user->role === UserRole::Admin;
    }

    private function isAssignedCoach(QaThread $thread, User $coach): bool
    {
        $thread->loadMissing('certification.coaches');

        return $thread->certification?->coaches->contains('id', $coach->id) ?? false;
    }
}
