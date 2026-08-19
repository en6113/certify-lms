<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Plan;

use App\Http\Requests\Plan\StoreRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 受講プラン新規作成 StoreRequest の rules() バリデーション検証。
 * name / description / duration_days / default_meeting_quota / sort_order の
 * 必須・文字数・数値レンジを Validator::make で網羅する。
 */
class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_valid_payload(): void
    {
        // Arrange
        $payload = [
            'name' => '3 ヶ月プラン 12 回',
            'description' => '標準的な学習期間のプランです',
            'duration_days' => 90,
            'default_meeting_quota' => 12,
            'sort_order' => 1,
        ];

        // Act
        $validator = Validator::make($payload, (new StoreRequest)->rules());

        // Assert
        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    #[DataProvider('invalidCases')]
    public function test_fails_for_invalid_field(string $field, mixed $value): void
    {
        // Arrange
        $payload = array_merge([
            'name' => 'Sample',
            'duration_days' => 90,
            'default_meeting_quota' => 12,
        ], [$field => $value]);

        // Act
        $validator = Validator::make($payload, (new StoreRequest)->rules());

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
            'name 未指定で エラー' => ['name', ''],
            'name 101 文字で エラー' => ['name', str_repeat('a', 101)],
            'description 2001 文字で エラー' => ['description', str_repeat('b', 2001)],
            'duration_days 0 で エラー' => ['duration_days', 0],
            'duration_days 3651 で エラー' => ['duration_days', 3651],
            'default_meeting_quota 負数で エラー' => ['default_meeting_quota', -1],
            'default_meeting_quota 1001 で エラー' => ['default_meeting_quota', 1001],
            'sort_order 負数で エラー' => ['sort_order', -1],
        ];
    }
}
