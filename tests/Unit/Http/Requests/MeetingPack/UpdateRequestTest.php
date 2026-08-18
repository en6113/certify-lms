<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Http\Requests\MeetingPack\UpdateRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 面談パック更新 UpdateRequest の rules() バリデーション検証。
 * name / description / meeting_count / price / stripe_price_id / sort_order の
 * 必須・文字数・数値レンジを Validator::make で網羅する(rules は Store と同じ)。
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_valid_payload(): void
    {
        // Arrange
        $payload = [
            'name' => '更新後の面談パック',
            'description' => '内容を更新した面談パックです',
            'meeting_count' => 2,
            'price' => 6000,
            'stripe_price_id' => 'price_1XyZaBcDeFgHiJk',
            'sort_order' => 15,
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
            'name' => 'Sample',
            'meeting_count' => 2,
            'price' => 6000,
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
            'name 未指定で エラー' => ['name', ''],
            'name 101 文字で エラー' => ['name', str_repeat('a', 101)],
            'description 2001 文字で エラー' => ['description', str_repeat('b', 2001)],
            'meeting_count 0 で エラー' => ['meeting_count', 0],
            'meeting_count 101 で エラー' => ['meeting_count', 101],
            'price 負数で エラー' => ['price', -1],
            'price 1000001 で エラー' => ['price', 1_000_001],
            'stripe_price_id 256 文字で エラー' => ['stripe_price_id', str_repeat('c', 256)],
            'sort_order 負数で エラー' => ['sort_order', -1],
        ];
    }
}
