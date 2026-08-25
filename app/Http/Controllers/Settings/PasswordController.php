<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

/**
 * パスワード変更 Controller。バリデーション自体は Fortify の UpdatesUserPasswords 実装
 * (App\Actions\Fortify\UpdateUserPassword)に委譲する。同 Action は 'updatePassword' エラーバッグに
 * 書き込むため、tab-password.blade.php の $errors->updatePassword 参照とそのまま噛み合う。
 */
class PasswordController extends Controller
{
    public function update(Request $request, UpdatesUserPasswords $updater): RedirectResponse
    {
        $updater->update($request->user(), $request->all());

        return redirect()->route('settings.profile.edit', ['tab' => 'password'])
            ->with('success', 'パスワードを変更しました。');
    }
}
