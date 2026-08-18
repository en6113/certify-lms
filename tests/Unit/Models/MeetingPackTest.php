<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MeetingPackStatus;
use App\Models\MeetingPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MeetingPack モデルのリレーション・Scope・Cast を検証する Unit テスト。
 * 2 リレーション (createdBy / updatedBy) + 3 scope (published / ordered / keyword) +
 * 4 cast (status enum / meeting_count int / price int / sort_order int) を網羅する。
 * 追加面談 SKU マスタ。
 */
class MeetingPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_by_relation_returns_admin(): void
    {
        // Arrange
        $admin = User::factory()->admin()->create();
        $pack = MeetingPack::factory()->create(['created_by_user_id' => $admin->id]);

        // Act
        $creator = $pack->createdBy;

        // Assert
        $this->assertTrue($creator->is($admin));
    }

    public function test_updated_by_relation_returns_admin(): void
    {
        // Arrange
        $updater = User::factory()->admin()->create();
        $pack = MeetingPack::factory()->create(['updated_by_user_id' => $updater->id]);

        // Act
        $lastUpdater = $pack->updatedBy;

        // Assert
        $this->assertTrue($lastUpdater->is($updater));
    }

    public function test_scope_published_filters_only_published(): void
    {
        // Arrange
        MeetingPack::factory()->draft()->create();
        $published = MeetingPack::factory()->published()->create();

        // Act
        $results = MeetingPack::published()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($published));
    }

    public function test_scope_ordered_sorts_by_sort_order(): void
    {
        // Arrange
        $second = MeetingPack::factory()->published()->create(['sort_order' => 2]);
        $first = MeetingPack::factory()->published()->create(['sort_order' => 1]);

        // Act
        $results = MeetingPack::ordered()->get();

        // Assert
        $this->assertTrue($results->first()->is($first));
    }

    public function test_scope_keyword_filters_by_partial_name_match(): void
    {
        // Arrange
        $matched = MeetingPack::factory()->published()->create(['name' => '追加面談3回パック']);
        MeetingPack::factory()->published()->create(['name' => '追加面談5回パック']);

        // Act
        $results = MeetingPack::keyword('3回')->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($matched));
    }

    public function test_scope_keyword_returns_all_when_keyword_is_null_or_empty(): void
    {
        // Arrange
        MeetingPack::factory()->published()->count(2)->create();

        // Act
        $withNull = MeetingPack::keyword(null)->get();
        $withEmpty = MeetingPack::keyword('')->get();

        // Assert
        $this->assertCount(2, $withNull);
        $this->assertCount(2, $withEmpty);
    }

    public function test_status_cast_converts_to_enum(): void
    {
        // Arrange
        $pack = MeetingPack::factory()->published()->create();

        // Act
        $fresh = $pack->fresh();

        // Assert
        $this->assertInstanceOf(MeetingPackStatus::class, $fresh->status);
        $this->assertSame(MeetingPackStatus::Published, $fresh->status);
    }

    public function test_integer_casts_return_int(): void
    {
        // Arrange
        $pack = MeetingPack::factory()->withCount(5)->withPrice(15000)->create();

        // Act
        $fresh = $pack->fresh();

        // Assert
        $this->assertIsInt($fresh->meeting_count);
        $this->assertSame(5, $fresh->meeting_count);
        $this->assertIsInt($fresh->price);
        $this->assertSame(15000, $fresh->price);
    }
}
