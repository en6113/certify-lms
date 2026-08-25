<?php

declare(strict_types=1);

namespace App\Http\Requests\QaThread;

use Illuminate\Foundation\Http\FormRequest;

/**
 * QaThread に質問スレッドを投稿する際の入力検証。
 *
 * 質問の投稿は受講中の受講生のみ（コーチ・adminは質問対象外）
 */
class UpdateThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('thread')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
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
            'title' => 'タイトル',
            'body' => '本文',
        ];
    }
}
