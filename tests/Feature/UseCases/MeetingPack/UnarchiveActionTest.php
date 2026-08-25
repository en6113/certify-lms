<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\MeetingPack;

use App\Enums\MeetingPackStatus;
use App\Exceptions\MeetingPack\MeetingPackInvalidTransitionException;
use App\Models\MeetingPack;
use App\Models\User;
use App\UseCases\MeetingPack\UnarchiveAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 面談パック下書き復帰(archived → draft) Action の状態遷移ガードを直接検証する。
 * アーカイブ済以外からの呼出は不正遷移として弾かれる。認可(admin のみ)は別途 MeetingPackPolicyTest / Feature\Http 側で検証する。
 */
class UnarchiveActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_unarchives_archived_meeting_pack(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->archived()->create();

        $result = app(UnarchiveAction::class)($meetingPack, $admin);

        $this->assertSame(MeetingPackStatus::Draft, $result->status);
        $this->assertDatabaseHas('meeting_packs', [
            'id' => $meetingPack->id,
            'status' => MeetingPackStatus::Draft->value,
            'updated_by_user_id' => $admin->id,
        ]);
    }

    public function test_throws_when_meeting_pack_is_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->draft()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        app(UnarchiveAction::class)($meetingPack, $admin);
    }

    public function test_throws_when_meeting_pack_is_published(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        $this->expectException(MeetingPackInvalidTransitionException::class);

        app(UnarchiveAction::class)($meetingPack, $admin);
    }

    public function test_status_is_unchanged_after_invalid_transition(): void
    {
        $admin = User::factory()->admin()->create();
        $meetingPack = MeetingPack::factory()->published()->create();

        try {
            app(UnarchiveAction::class)($meetingPack, $admin);
            $this->fail('MeetingPackInvalidTransitionException が throw されるはず');
        } catch (MeetingPackInvalidTransitionException) {
            // 期待通り
        }

        $this->assertDatabaseHas('meeting_packs', [
            'id' => $meetingPack->id,
            'status' => MeetingPackStatus::Published->value,
        ]);
    }
}
