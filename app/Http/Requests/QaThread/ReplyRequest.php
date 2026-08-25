<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

/**
 * QaThread の質問スレッドに回答を投稿する際の入力検証。
 *
 * 回答者は受講生と担当資格のコーチのみ（管理者は回答できない）
 */
class ReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return $role === UserRole::Student
            || $role === UserRole::Coach;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'body' => '回答本文',
        ];
    }
}
