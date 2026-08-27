<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Enrollment;
use App\Models\EnrollmentNote;
use App\Models\User;

/**
 * EnrollmentNote(受講登録メモ)リソースに対する認可ポリシー。
 *
 * - viewAny / create: 当該資格の担当コーチ / 管理者のみ(受講生は閲覧含め全拒否)
 * - update / delete: 作成者本人 / 管理者
 */
class EnrollmentNotePolicy
{
    public function viewAny(User $user, Enrollment $enrollment): bool
    {
        return match (true) {
            $user->role === UserRole::Admin => true,
            $user->role === UserRole::Coach => $this->isAssignedCoach($enrollment, $user),
            default => false,
        };
    }

    public function create(User $user, Enrollment $enrollment): bool
    {
        return match (true) {
            $user->role === UserRole::Admin => true,
            $user->role === UserRole::Coach => $this->isAssignedCoach($enrollment, $user),
            default => false,
        };
    }

    public function update(User $user, EnrollmentNote $note): bool
    {
        return $note->created_by_user_id === $user->id
            || $user->role === UserRole::Admin;
    }

    public function delete(User $user, EnrollmentNote $note): bool
    {
        return $note->created_by_user_id === $user->id
            || $user->role === UserRole::Admin;
    }

    private function isAssignedCoach(Enrollment $enrollment, User $coach): bool
    {
        $enrollment->loadMissing('certification.coaches');

        return $enrollment->certification?->coaches->contains('id', $coach->id) ?? false;
    }
}
