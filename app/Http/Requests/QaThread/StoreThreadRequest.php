<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use App\Enums\CertificationStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * QaThread に質問スレッドを投稿する際の入力検証。
 *
 * 質問の投稿は受講中の受講生のみ（コーチ・adminは質問対象外）
 */
class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->user()?->role;

        return $role === UserRole::Student;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'certification_id' => [
                'required',
                'ulid',
                // 質問できる Certification は公開中のみ(draft / archived は弾く)
                Rule::exists('certifications', 'id')->where('status', CertificationStatus::Published->value),
            ],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'certification_id' => '資格',
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }
}
