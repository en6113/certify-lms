<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentNote;

use App\Models\EnrollmentNote;

/**
 * EnrollmentNote(受講登録メモ)を新規作成するユースケース。
 */
final class StoreAction
{
    /**
     * @param array{enrollment_id: string, created_by_user_id: string, body: string} $validated
     */
    public function __invoke(array $validated): EnrollmentNote
    {
        return EnrollmentNote::create($validated);
    }
}
