<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\QaThread;
use App\Models\User;

/**
 * QaThread(質問掲示板)　リソースに対する認可ポリシー。
 *
 * - viewAny: admin / coach / student いずれかであれば一覧自体は閲覧可(取得スコープは Controller / Action 側で絞る)
 * - view: 受講中の受講生 / 当該資格の担当コーチ / admin
 * - create: 受講中の受講生のみ
 * - update/delete/resolved/unresolved: 投稿者本人のみ
 *
 * coach の判定は certification.coaches リレーション(certification_coach_assignments 経由)で行う。
 */
class QaThreadPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Coach, UserRole::Student], true);
    }

    public function view(User $user, QaThread $thread): bool
    {
        return match (true) {
            $user->role === UserRole::Admin => true,
            $user->role === UserRole::Student => $user->status === UserStatus::InProgress,
            $user->role === UserRole::Coach => $this->isAssignedCoach($thread, $user),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Student
            && $user->status === UserStatus::InProgress;
    }

    public function update(User $user, QaThread $thread): bool
    {
        return $this->isOwnedByActiveStudent($user, $thread);
    }

    public function delete(User $user, QaThread $thread): bool
    {
        return $this->isOwnedByActiveStudent($user, $thread);
    }

    public function resolve(User $user, QaThread $thread): bool
    {
        return $this->isOwnedByActiveStudent($user, $thread);
    }

    public function unresolve(User $user, QaThread $thread): bool
    {
        return $this->isOwnedByActiveStudent($user, $thread);
    }

    private function isOwnedByActiveStudent(User $user, QaThread $thread): bool
    {
        return $user->role === UserRole::Student
            && $user->status === UserStatus::InProgress
            && $thread->user_id === $user->id;
    }

    private function isAssignedCoach(QaThread $thread, User $coach): bool
    {
        $thread->loadMissing('certification.coaches');

        return $thread->certification?->coaches->contains('id', $coach->id) ?? false;
    }
}
