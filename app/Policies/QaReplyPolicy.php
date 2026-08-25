<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\QaReply;
use App\Models\QaThread;
use App\Models\User;

/**
 * QaReply(質問回答)リソースに対する認可ポリシー。
 *
 * - view: 受講中の受講生 / 当該資格の担当コーチ / 管理者
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
            $user->role === UserRole::Student => $user->status === UserStatus::InProgress
                && $this->certificationIsPublished($thread),
            $user->role === UserRole::Coach => $this->isAssignedCoach($thread, $user)
                && $this->certificationIsPublished($thread),
            default => false,
        };
    }

    public function create(User $user, QaThread $thread): bool
    {
        $canParticipate = match (true) {
            $user->role === UserRole::Student => $user->status === UserStatus::InProgress,
            $user->role === UserRole::Coach => $this->isAssignedCoach($thread, $user),
            default => false,
        };

        return $canParticipate && $this->certificationIsPublished($thread);
    }

    public function update(User $user, QaReply $reply): bool
    {
        return $reply->reply_user_id === $user->id;
    }

    public function delete(User $user, QaReply $reply): bool
    {
        return $reply->reply_user_id === $user->id
            || $user->role === UserRole::Admin;
    }

    private function isAssignedCoach(QaThread $thread, User $coach): bool
    {
        $thread->loadMissing('certification.coaches');

        return $thread->certification?->coaches->contains('id', $coach->id) ?? false;
    }

    private function certificationIsPublished(QaThread $thread): bool
    {
        $thread->loadMissing('certification');

        return $thread->certification?->status === CertificationStatus::Published;
    }
}
