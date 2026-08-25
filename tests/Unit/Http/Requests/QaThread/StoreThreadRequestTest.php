<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\QaThread;

use App\Http\Requests\QaThread\StoreThreadRequest;
use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * QaThread 新規投稿 StoreThreadRequest の rules() を検証する Unit テスト。
 * certification_id は「公開中の資格のみ」を Rule::exists の where 条件で絞っている点を重点的に確認する。
 */
class StoreThreadRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_with_valid_payload(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'title' => 'ネットワークの基礎について',
            'body' => 'サブネットマスクの計算方法が分かりません。',
        ], (new StoreThreadRequest)->rules());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_fails_when_certification_id_missing(): void
    {
        $validator = Validator::make([
            'title' => 'タイトル',
            'body' => '本文',
        ], (new StoreThreadRequest)->rules());

        $this->assertArrayHasKey('certification_id', $validator->errors()->toArray());
    }

    public function test_fails_when_certification_is_not_published(): void
    {
        $certification = Certification::factory()->draft()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'title' => 'タイトル',
            'body' => '本文',
        ], (new StoreThreadRequest)->rules());

        $this->assertArrayHasKey('certification_id', $validator->errors()->toArray(), '下書き中の資格には質問できない');
    }

    public function test_fails_when_title_missing(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'body' => '本文',
        ], (new StoreThreadRequest)->rules());

        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_fails_when_title_exceeds_200_chars(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'title' => str_repeat('a', 201),
            'body' => '本文',
        ], (new StoreThreadRequest)->rules());

        $this->assertArrayHasKey('title', $validator->errors()->toArray());
    }

    public function test_fails_when_body_missing(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'title' => 'タイトル',
        ], (new StoreThreadRequest)->rules());

        $this->assertArrayHasKey('body', $validator->errors()->toArray());
    }

    public function test_fails_when_body_exceeds_5000_chars(): void
    {
        $certification = Certification::factory()->published()->create();

        $validator = Validator::make([
            'certification_id' => $certification->id,
            'title' => 'タイトル',
            'body' => str_repeat('a', 5001),
        ], (new StoreThreadRequest)->rules());

        $this->assertArrayHasKey('body', $validator->errors()->toArray());
    }
}
