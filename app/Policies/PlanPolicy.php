<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\User;

/**
 * 受講プラン(Plan)マスタの認可ルール。全操作 admin 限定(coach / student はアクセス不可)。
 *
 * 状態遷移の順序制約(下書き→公開中→アーカイブ→下書き)や削除可否(公開中は削除不可)といった
 * 業務ルールは本 Policy では判定しない。MeetingPackPolicy / DestroyAction と同様、
 * 「誰が実行してよいか」だけを Policy が持ち、「今この状態で実行してよいか」は UseCase 側で
 * 判定し、違反時は専用の Exception を投げる。
 */
class PlanPolicy
{
    public function viewAny(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function view(User $auth, Plan $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function create(User $auth): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function update(User $auth, Plan $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function delete(User $auth, Plan $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function publish(User $auth, Plan $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function archive(User $auth, Plan $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }

    public function unarchive(User $auth, Plan $plan): bool
    {
        return $auth->role === UserRole::Admin;
    }
}
