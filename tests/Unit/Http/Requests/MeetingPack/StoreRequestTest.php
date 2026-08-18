<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Http\Requests\MeetingPack\StoreRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * 面談パック新規作成 StoreRequest の rules() バリデーション検証。
 * name / description / meeting_count / price / stripe_price_id / sort_order の
 * 必須・文字数・数値レンジを Validator::make で網羅する。
 */
class StoreRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_valid_payload(): void
    {
        // Arrange
        $payload = [
            'name' => '2回パック',
            'description' => '通常プランに追加できる面談2回分のパックです',
            'meeting_count' => 2,
            'price' => 5000,
            'stripe_price_id' => 'price_1AbCdEfGhIjKlMn',
            'sort_order' => 15,
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
            'meeting_count' => 2,
            'price' => 5000,
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
            'meeting_count 0 で エラー' => ['meeting_count', 0],
            'meeting_count 101 で エラー' => ['meeting_count', 101],
            'price 負数で エラー' => ['price', -1],
            'price 1000001 で エラー' => ['price', 1_000_001],
            'stripe_price_id 256 文字で エラー' => ['stripe_price_id', str_repeat('c', 256)],
            'sort_order 負数で エラー' => ['sort_order', -1],
        ];
    }
}
