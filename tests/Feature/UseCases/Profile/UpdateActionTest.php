<?php

declare(strict_types=1);

namespace Tests\Feature\UseCases\Profile;

use App\Models\User;
use App\UseCases\Profile\UpdateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * プロフィール更新 UpdateAction のユースケース検証。
 * FormRequest を経由せず Action を直接呼び出し、「コーチ以外は meeting_url を無視する」という
 * 業務ルールが Action 自身の責務として成立していることを確認する。
 */
class UpdateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_name_and_bio_for_student(): void
    {
        // Arrange
        $student = User::factory()->student()->create(['name' => '旧名前', 'bio' => '旧自己紹介']);

        // Act
        $result = app(UpdateAction::class)($student, [
            'name' => '新しい名前',
            'bio' => '新しい自己紹介',
        ]);

        // Assert
        $this->assertSame('新しい名前', $result->name);
        $this->assertSame('新しい自己紹介', $result->bio);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => '新しい名前',
            'bio' => '新しい自己紹介',
        ]);
    }

    public function test_updates_meeting_url_for_coach(): void
    {
        // Arrange
        $coach = User::factory()->coach()->create(['meeting_url' => null]);

        // Act
        $result = app(UpdateAction::class)($coach, [
            'name' => 'コーチ花子',
            'bio' => null,
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ]);

        // Assert
        $this->assertSame('https://meet.google.com/abc-defg-hij', $result->meeting_url);
        $this->assertDatabaseHas('users', [
            'id' => $coach->id,
            'meeting_url' => 'https://meet.google.com/abc-defg-hij',
        ]);
    }

    #[DataProvider('nonCoachRoleFactories')]
    public function test_ignores_meeting_url_for_non_coach_roles(string $factoryState): void
    {
        // Arrange: FormRequest を経由しない直接呼び出しを想定し、非コーチが meeting_url を
        // 含む入力を渡してきたケースを再現する(不正な直接APIコールや将来の呼び出し元を想定した多層防御の確認)。
        $user = User::factory()->{$factoryState}()->create(['meeting_url' => null]);

        // Act
        $result = app(UpdateAction::class)($user, [
            'name' => '名前',
            'bio' => null,
            'meeting_url' => 'https://meet.google.com/should-be-ignored',
        ]);

        // Assert: meeting_url は無視され、null のまま
        $this->assertNull($result->meeting_url);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'meeting_url' => null,
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function nonCoachRoleFactories(): array
    {
        return [
            '受講生の場合' => ['student'],
            '管理者の場合' => ['admin'],
        ];
    }
}
