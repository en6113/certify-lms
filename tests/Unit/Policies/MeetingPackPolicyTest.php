<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\MeetingPack;
use App\Models\User;
use App\Policies\MeetingPackPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * MeetingPackPolicy の判定を検証する Unit テスト。
 * 8 ability (viewAny/view/create/update/delete/publish/archive/unarchive) ×
 * admin(true)/coach(false)/student(false) を DataProvider で網羅する。
 *
 * MeetingPackPolicy は状態(下書き/公開中/アーカイブ)や削除可否といった業務ルールを持たず
 * 「admin かどうか」のみを判定する。
 */
class MeetingPackPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('adminOnlyAbilityMatrix')]
    public function test_ability_matches_role_expectation(
        string $actingRole,
        string $policyMethod,
        bool $expected,
    ): void {
        // Arrange
        $actor = User::factory()->{$actingRole}()->create();
        $meetingPack = MeetingPack::factory()->create();
        $policy = new MeetingPackPolicy;

        // Act
        $result = match ($policyMethod) {
            'create', 'viewAny' => $policy->{$policyMethod}($actor),
            default => $policy->{$policyMethod}($actor, $meetingPack),
        };

        // Assert
        $this->assertSame(
            $expected,
            $result,
            "{$actingRole} が {$policyMethod} で ".($expected ? 'true' : 'false').' を返すはず',
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function adminOnlyAbilityMatrix(): array
    {
        $abilities = [
            'viewAny', 'view', 'create', 'update', 'delete', 'publish', 'archive', 'unarchive',
        ];
        $roles = [
            'admin' => true,
            'coach' => false,
            'student' => false,
        ];

        $cases = [];
        foreach ($roles as $role => $expected) {
            foreach ($abilities as $ability) {
                $caseKey = $expected
                    ? "{$role} は {$ability} を実行できる"
                    : "{$role} は {$ability} を実行できない";
                $cases[$caseKey] = [$role, $ability, $expected];
            }
        }

        return $cases;
    }
}
