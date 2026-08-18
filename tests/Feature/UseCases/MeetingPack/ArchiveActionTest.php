<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackInvalidTransitionException;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\ArchiveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談パックアーカイブ(published → archived) Action の状態遷移ガードを直接検証する。
 * 公開中以外からの呼出は不正遷移として弾かれる。認可(admin のみ)は別途 MeetingPackPolicyTest / Feature\Http 側で検証する。
 */
class ArchiveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_archives_published_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $result = app(ArchiveAction::class)($meetingPack, $admin);

        $this->assertSame(MeetingPackStatus::Archived, $result->status);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $meetingPack->id,
            'status' => MeetingPackStatus::Archived->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_throws_when_meeting_pack_is_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->draft()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        app(ArchiveAction::class)($meetingPack, $admin);
    }

    public function test_throws_when_meeting_pack_is_already_archived(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->archived()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        app(ArchiveAction::class)($meetingPack, $admin);
    }

    public function test_status_is_unchanged_after_invalid_transition(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->draft()->create();

        try {
            app(ArchiveAction::class)($meetingPack, $admin);
            $this->fail('MeetingPackInvalidTransitionException が throw されるはず');
        } catch (MeetingPackInvalidTransitionException) {
            // 期待通り
        }

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $meetingPack->id,
            'status' => MeetingPackStatus::Draft->value,
        ]);
    }
}
