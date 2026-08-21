<?php

declare(strict_types=1);

namespace App\Http\Requests\Avatar;

use Illuminate\Foundation\Http\FormRequest;

/**
 * アイコン画像アップロードリクエスト。png / jpg / jpeg / webp の 2MB 以下に制限する
 */
class StoreRequest extends FormRequest
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
        return [
            'avatar' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'avatar' => '画像ファイル',
        ];
    }
}
