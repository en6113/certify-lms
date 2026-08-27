<?php

declare(strict_types=1);

namespace App\UseCases\EnrollmentNote;

use App\Models\EnrollmentNote;

/**
 * EnrollmentNote(受講登録メモ)を削除するユースケース。
 */
final class DestroyAction
{
    public function __invoke(EnrollmentNote $note): void
    {
        $note->delete();
    }
}
