<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

/**
 * 本人プロフィール(氏名 / 自己紹介 / 固定面談URL)の更新リクエスト。
 *
 * 対象は常にログイン中の本人自身のため authorize() は auth 済であれば true。
 * meeting_url はコーチのみ入力欄が存在するが、他ロールが直接送信してきた場合に備え、
 * rules() ではコーチのときだけバリデーション対象に加える(非コーチの値は Action 側で無視する)。
 */
class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->user()?->role === UserRole::Coach) {
            $rules['meeting_url'] = ['nullable', 'url', 'max:500'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '氏名',
            'bio' => '自己紹介',
            'meeting_url' => '固定面談URL',
        ];
    }
}
