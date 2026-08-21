<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * プロフィール更新 UpdateRequest のバリデーション検証。
 * 必須 name / bio・meeting_url の文字数制限を valid + invalid で網羅し、
 * meeting_url がコーチのときだけバリデーション対象になることを確認する。
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_passes_with_valid_payload(): void
    {
        // Arrange
        $student = User::factory()->student()->create();

        // Act
        $response = $this->actingAs($student)->patch(route('settings.profile.update'), [
            'name' => '受講太郎',
            'bio' => '基本情報技術者試験に挑戦中です。',
        ]);

        // Assert
        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('settings.profile.edit'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => '受講太郎']);
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation_fails(array $overrides, string $expectedErrorField): void
    {
        // Arrange
        $student = User::factory()->student()->create();
        $payload = array_merge([
            'name' => '受講太郎',
            'bio' => '自己紹介',
        ], $overrides);

        // Act
        $response = $this->actingAs($student)->patchJson(route('settings.profile.update'), $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    #[DataProvider('invalidMeetingUrlPayloads')]
    public function test_meeting_url_validation_fails_for_coach(array $overrides, string $expectedErrorField): void
    {
        // Arrange
        $coach = User::factory()->coach()->create();
        $payload = array_merge([
            'name' => 'コーチ花子',
            'bio' => null,
        ], $overrides);

        // Act
        $response = $this->actingAs($coach)->patchJson(route('settings.profile.update'), $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors($expectedErrorField);
    }

    public function test_meeting_url_validation_is_skipped_for_student(): void
    {
        // Arrange
        $student = User::factory()->student()->create();

        // Act
        $response = $this->actingAs($student)->patch(route('settings.profile.update'), [
            'name' => '受講太郎',
            'bio' => null,
            'meeting_url' => 'これはURLではない',
        ]);

        // Assert: meeting_url はコーチ以外だとルール自体が存在しないため、バリデーションエラーにならない
        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('settings.profile.edit'));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'name 未指定で 422' => [['name' => ''], 'name'],
            'name 51 文字で 422' => [['name' => str_repeat('あ', 51)], 'name'],
            'bio 1001 文字で 422' => [['bio' => str_repeat('あ', 1001)], 'bio'],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function invalidMeetingUrlPayloads(): array
    {
        return [
            'meeting_url がURL形式でない場合' => [['meeting_url' => 'これはURLではない'], 'meeting_url'],
            'meeting_url が501文字の場合' => [
                ['meeting_url' => 'https://example.com/'.str_repeat('a', 490)],
                'meeting_url',
            ],
        ];
    }
}
