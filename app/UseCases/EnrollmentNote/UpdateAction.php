<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentNote;

use App\Models\EnrollmentNote;

/**
 * EnrollmentNote(受講登録メモ)を更新するユースケース。
 */
final class UpdateAction
{
    /**
     * @param array{body: string} $validated
     */
    public function __invoke(EnrollmentNote $note, array $validated): EnrollmentNote
    {
        $note->update($validated);

        return $note->fresh();
    }
}
