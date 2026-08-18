<?php

declare(strict_types=1);

namespace App\UseCases\MeetingPack;

use App\Models\MeetingPack;

/**
 * admin 用の面談パック詳細を取得するユースケース。作成者 / 最終更新者を Eager Loading で揃える。
 */
final class ShowAction
{
    public function __invoke(MeetingPack $meetingPack): MeetingPack
    {
        return $meetingPack->load(['createdBy', 'updatedBy']);
    }
}
