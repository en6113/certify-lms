<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\EnrollmentNote;

use App\Http\Requests\EnrollmentNote\UpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 受講登録メモ更新 updateRequest の rules() バリデーション検証。
 * body の必須・文字数上限を Validator::make で確認する。
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_valid_payload(): void
    {
        // Arrange
        $payload = [
            'body' => '更新後のメモ本文です。',
        ];

        // Act
        $validator = Validator::make($payload, (new UpdateRequest)->rules());

        // Assert
        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    #[DataProvider('invalidCases')]
    public function test_fails_for_invalid_field(string $field, mixed $value): void
    {
        // Arrange
        $payload = array_merge([
            'body' => 'サンプル本文',
        ], [$field => $value]);

        // Act
        $validator = Validator::make($payload, (new UpdateRequest)->rules());

        // Assert
        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey($field, $validator->errors()->toArray());
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function invalidCases(): array
    {
        return [
            'body 未指定で エラー' => ['body', ''],
            'body 空文字で エラー' => ['body', '   '],
            'body 2001 文字で エラー' => ['body', str_repeat('あ', 2001)],
        ];
    }
}
