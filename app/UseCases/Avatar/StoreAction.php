<?php

declare(strict_types=1);

namespace App\UseCases\Avatar;

use App\Exceptions\Avatar\AvatarStorageException;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * アイコン画像のアップロードユースケース。
 *
 *  `avatars/{ulid}.{ext}` 形式で public disk に保存し、users.avatar_url にはそのまま <img src> に使える
 * ルート相対パス('/storage/avatars/...')を保存する。
 * 新しい画像の保存は、Storage 保存と DB UPDATE を単一トランザクション内で実行し、
 * いずれかが失敗した場合は ROLLBACK + 保険的に Storage を削除して orphan ファイルを残さない。
 * 古い画像の削除は SectionImage の DestroyAction と同じ方法で、commit 後にのみ実ファイルを消すことで、
 * ロールバック時に古い画像を失わないようにする。
 */
final class StoreAction
{
    /**
     * @throws AvatarStorageException
     */
    public function __invoke(User $user, UploadedFile $file): User
    {
        $ulid = (string) Str::ulid();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $relativePath = "avatars/{$ulid}.{$ext}";

        try {
            return DB::transaction(function () use ($user, $file, $relativePath): User {
                $previousRelativePath = $this->relativePathFromUrl($user->avatar_url);

                Storage::disk('public')->putFileAs(
                    'avatars',
                    $file,
                    basename($relativePath)
                );
                $user->update(['avatar_url' => '/storage/'.$relativePath]);

                if ($previousRelativePath !== null) {
                    DB::afterCommit(fn () => Storage::disk('public')->delete($previousRelativePath));
                }

                return $user->refresh();
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($relativePath);
            throw new AvatarStorageException($e);
        }
    }

    private function relativePathFromUrl(?string $url): ?string
    {
        if ($url === null || ! str_starts_with($url, '/storage/')) {
            return null;
        }

        return Str::after($url, '/storage/');
    }
}
