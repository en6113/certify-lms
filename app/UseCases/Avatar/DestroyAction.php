<?php

declare(strict_types=1);

namespace App\UseCases\Avatar;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * アイコン画像削除ユースケース。
 *
 * DB を先に更新(avatar_url = null)し、commit 後に Storage 上の実ファイルを削除する
 * トランザクション ROLLBACK 時には Storage 削除をスキップし、不可逆な実ファイル削除を防ぐ。
 */
final class DestroyAction
{
    public function __invoke(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            $relativePath = $this->relativePathFromUrl($user->avatar_url);

            $user->update(['avatar_url' => null]);

            if ($relativePath !== null) {
                DB::afterCommit(fn () => Storage::disk('public')->delete($relativePath));
            }

            return $user->refresh();
        });
    }

    private function relativePathFromUrl(?string $url): ?string
    {
        if ($url === null || ! str_starts_with($url, '/storage/')) {
            return null;
        }

        return Str::after($url, '/storage/');
    }
}
