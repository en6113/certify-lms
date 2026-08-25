<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\QaThread;

use App\Http\Requests\QaThread\UpdateThreadRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * QaThread 更新 UpdateThreadRequest の rules() を検証する Unit テスト。
 * title / body の必須 + 文字数上限を StoreThreadRequest と同様に確認する
 * (certification_id は更新対象外のため rules() に含まれない)。
 */
class UpdateThreadRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_valid_payload(): void
    {
        $validator = Validator::make([
            'title' => '更新後タイトル',
            'body' => '更新後本文',
        ], (new UpdateThreadRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_title_missing(): void
    {
        $validator = Validator::make(['body' => '本文'], (new UpdateThreadRequest)->rules());

        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_fails_when_title_exceeds_200_chars(): void
    {
        $validator = Validator::make([
            'title' => str_repeat('a', 201),
            'body' => '本文',
        ], (new UpdateThreadRequest)->rules());

        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_fails_when_body_missing(): void
    {
        $validator = Validator::make(['title' => 'タイトル'], (new UpdateThreadRequest)->rules());

        $this->assertArrayHasKey('body', $validator->errors()->toArray());
    }

    public function test_fails_when_body_exceeds_5000_chars(): void
    {
        $validator = Validator::make([
            'title' => 'タイトル',
            'body' => str_repeat('a', 5001),
        ], (new UpdateThreadRequest)->rules());

        $this->assertArrayHasKey('body', $validator->errors()->toArray());
    }
}
