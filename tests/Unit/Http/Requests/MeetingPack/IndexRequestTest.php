<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\MeetingPack;

use App\Http\Requests\MeetingPack\IndexRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * 面談パック一覧 IndexRequest の rules() を検証する Unit テスト。
 * keyword (nullable + max:100) / status (nullable + enum) / page (nullable + integer + min:1) を網羅する。
 */
class IndexRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_empty_filters(): void
    {
        $validator = Validator::make([], (new IndexRequest)->rules());
        $this->assertTrue($validator->passes());
    }

    public function test_passes_with_valid_filters(): void
    {
        $validator = Validator::make([
            'keyword' => '5回',
            'status' => 'published',
            'page' => 2,
        ], (new IndexRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_keyword_exceeds_max(): void
    {
        $validator = Validator::make(['keyword' => str_repeat('a', 101)], (new IndexRequest)->rules());
        $this->assertArrayHasKey('keyword', $validator->errors()->toArray());
    }

    public function test_fails_when_status_is_invalid_value(): void
    {
        $validator = Validator::make(['status' => 'unknown'], (new IndexRequest)->rules());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_fails_when_page_is_less_than_one(): void
    {
        $validator = Validator::make(['page' => 0], (new IndexRequest)->rules());
        $this->assertArrayHasKey('page', $validator->errors()->toArray());
    }
}
