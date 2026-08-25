<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\QaThread;

use App\Enums\QaThreadStatus;
use App\Http\Requests\QaThread\IndexRequest;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * QaThread 一覧 IndexRequest の rules() を検証する Unit テスト。
 * status(enum) / certification_id(ulid + 存在確認) / keyword の nullable フィルタを網羅する。
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
        $certification = Certification::factory()->create();

        $validator = Validator::make([
            'status' => QaThreadStatus::Resolved->value,
            'certification_id' => $certification->id,
            'keyword' => 'IAM',
        ], (new IndexRequest)->rules());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_fails_when_status_is_not_a_valid_enum_value(): void
    {
        $validator = Validator::make(['status' => 'unknown'], (new IndexRequest)->rules());

        $this->assertArrayHasKey('status', $validator->errors()->toArray());
    }

    public function test_fails_when_certification_id_not_ulid(): void
    {
        $validator = Validator::make(['certification_id' => 'not-ulid'], (new IndexRequest)->rules());

        $this->assertArrayHasKey('certification_id', $validator->errors()->toArray());
    }

    public function test_fails_when_certification_id_does_not_exist(): void
    {
        $validator = Validator::make(['certification_id' => (string) Str::ulid()], (new IndexRequest)->rules());

        $this->assertArrayHasKey('certification_id', $validator->errors()->toArray());
    }

    public function test_fails_when_keyword_exceeds_100_chars(): void
    {
        $validator = Validator::make(['keyword' => str_repeat('a', 101)], (new IndexRequest)->rules());

        $this->assertArrayHasKey('keyword', $validator->errors()->toArray());
    }
}
