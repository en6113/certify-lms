<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use App\Enums\QaThreadStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * QaThread 一覧アクセスの入力検証。受講生 / コーチ / admin 共通で利用される。
 *
 * - 共通: `status` / `certification_id` / `keyword` でフィルタ可
 * - 受講生: 公開資格すべてのスレッド
 * - コーチ: 担当資格のスレッドのみ
 * - admin: 公開停止中の資格含め、すべての資格のスレッド
 */
class IndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return $role === UserRole::Student
            || $role === UserRole::Coach
            || $role === UserRole::Admin;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(QaThreadStatus::class)],
            'certification_id' => ['nullable', 'ulid', 'exists:certifications,id'],
            'keyword' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => '解決状況',
            'certification_id' => '資格',
            'keyword' => 'キーワード',
        ];
    }

    /**
     * @return array{status: ?string, certification_id: ?string, keyword: ?string}
     */
    public function filters(): array
    {
        return [
            'status' => $this->input('status'),
            'certification_id' => $this->input('certification_id'),
            'keyword' => $this->input('keyword'),
        ];
    }
}
