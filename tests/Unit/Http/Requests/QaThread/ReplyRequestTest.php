<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\QaThread;

use App\Http\Requests\QaThread\ReplyRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * QaThread への回答 ReplyRequest の rules() を検証する Unit テスト。
 * body の必須 + 文字数上限(5000)を Validator::make で網羅する。
 */
class ReplyRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_valid_body(): void
    {
        $validator = Validator::make(['body' => '回答本文です。'], (new ReplyRequest)->rules());

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_body_missing(): void
    {
        $validator = Validator::make([], (new ReplyRequest)->rules());

        $this->assertArrayHasKey('body', $validator->errors()->toArray());
    }

    public function test_fails_when_body_exceeds_5000_chars(): void
    {
        $validator = Validator::make(['body' => str_repeat('a', 5001)], (new ReplyRequest)->rules());

        $this->assertArrayHasKey('body', $validator->errors()->toArray());
    }
}
