<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Exceptions\MeetingPack\MeetingPackNotDeletableException;
use App\Models\MeetingPack;
use App\UseCases\MeetingPack\DestroyAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談パック削除 Action のガード処理を直接検証する。
 * 公開中の面談パックは削除不可(過去の購入履歴の整合性を守るため)。
 */
class DestroyActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_deletes_draft_meeting_pack(): void
    {
        $meetingPack = MeetingPack::factory()->draft()->create();

        app(DestroyAction::class)($meetingPack);

        $this->assertDatabaseMissing('meeting_packs', ['id' => $meetingPack->id]);
    }

    public function test_successfully_deletes_archived_meeting_pack(): void
    {
        $meetingPack = MeetingPack::factory()->archived()->create();

        app(DestroyAction::class)($meetingPack);

        $this->assertDatabaseMissing('meeting_packs', ['id' => $meetingPack->id]);
    }

    public function test_throws_when_meeting_pack_is_published(): void
    {
        $meetingPack = MeetingPack::factory()->published()->create();

        $this->expectException(MeetingPackNotDeletableException::class);

        app(DestroyAction::class)($meetingPack);
    }

    public function test_published_meeting_pack_is_not_deleted_after_exception(): void
    {
        $meetingPack = MeetingPack::factory()->published()->create();

        try {
            app(DestroyAction::class)($meetingPack);
            $this->fail('MeetingPackNotDeletableException が throw されるはず');
        } catch (MeetingPackNotDeletableException) {
            // 期待通り
        }

        $this->assertDatabaseHas('meeting_packs', ['id' => $meetingPack->id]);
    }
}
