<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Section;

use App\Models\Certification;
use App\Models\Chapter;
use App\Models\Part;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ContentTestHelpers;
use Tests\TestCase;

class CrudTest extends TestCase
{
    use ContentTestHelpers, RefreshDatabase;

    public function test_admin_can_create_section(): void
    {
        $admin = User::factory()->admin()->create();
        $cert = Certification::factory()->published()->create();
        $part = Part::factory()->forCertification($cert)->draft()->create();
        $chapter = Chapter::factory()->forPart($part)->draft()->create();

        $this->actingAs($admin)
            ->post(route('admin.chapters.sections.store', $chapter), [
                'title' => 'はじめに',
                'body' => '## 概要
                本セクションでは...',
            ])
            ->assertRedirect();

        $section = Section::where('title', 'はじめに')->firstOrFail();
        $this->assertStringContainsString('概要', $section->body);
        $this->assertSame('draft', $section->status->value);
    }

    public function test_assigned_coach_can_create_section(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        $part = Part::factory()->forCertification($cert)->draft()->create();
        $chapter = Chapter::factory()->forPart($part)->draft()->create();

        $this->actingAs($coach)
            ->post(route('admin.chapters.sections.store', $chapter), [
                'title' => 'はじめに',
                'body' => '本文',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sections', ['chapter_id' => $chapter->id, 'title' => 'はじめに']);
    }

    public function test_non_assigned_coach_cannot_create_section(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $part = Part::factory()->forCertification($cert)->draft()->create();
        $chapter = Chapter::factory()->forPart($part)->draft()->create();

        $this->actingAs($coach)
            ->post(route('admin.chapters.sections.store', $chapter), [
                'title' => 'はじめに',
                'body' => '本文',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_update_updates_section(): void
    {
        $admin = User::factory()->admin()->create();
        [$part, $chapter, $section] = $this->makePartChain(Certification::factory()->published()->create(), 'draft');

        $this->actingAs($admin)
            ->patch(route('admin.sections.update', $section), [
                'title' => '新タイトル',
                'body' => 'updated body',
            ])
            ->assertRedirect();

        $section->refresh();
        $this->assertSame('新タイトル', $section->title);
        $this->assertSame('updated body', $section->body);
    }

    public function test_assigned_coach_can_update_section(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        [$part, $chapter, $section] = $this->makePartChain($cert, 'draft');

        $this->actingAs($coach)
            ->patch(route('admin.sections.update', $section), [
                'title' => '新タイトル',
                'body' => 'updated body',
            ])
            ->assertRedirect();

        $this->assertSame('新タイトル', $section->fresh()->title);
    }

    public function test_non_assigned_coach_cannot_update_section(): void
    {
        $coach = User::factory()->coach()->create();
        [$part, $chapter, $section] = $this->makePartChain(Certification::factory()->published()->create(), 'draft');

        $this->actingAs($coach)
            ->patchJson(route('admin.sections.update', $section), [
                'title' => '新タイトル',
                'body' => 'updated body',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_publish_and_unpublish_section(): void
    {
        $admin = User::factory()->admin()->create();
        [$part, $chapter, $section] = $this->makePartChain(Certification::factory()->published()->create(), 'draft');

        $this->actingAs($admin)
            ->post(route('admin.sections.publish', $section))
            ->assertRedirect(route('admin.sections.show', $section));
        $this->assertSame('published', $section->fresh()->status->value);

        $this->actingAs($admin)
            ->post(route('admin.sections.unpublish', $section))
            ->assertRedirect(route('admin.sections.show', $section));
        $this->assertSame('draft', $section->fresh()->status->value);
    }

    public function test_assigned_coach_can_publish_and_unpublish_section(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        [$part, $chapter, $section] = $this->makePartChain($cert, 'draft');

        $this->actingAs($coach)
            ->post(route('admin.sections.publish', $section))
            ->assertRedirect(route('admin.sections.show', $section));
        $this->assertSame('published', $section->fresh()->status->value);

        $this->actingAs($coach)
            ->post(route('admin.sections.unpublish', $section))
            ->assertRedirect(route('admin.sections.show', $section));
        $this->assertSame('draft', $section->fresh()->status->value);
    }

    public function test_non_assigned_coach_cannot_publish_section(): void
    {
        $coach = User::factory()->coach()->create();
        [$part, $chapter, $section] = $this->makePartChain(Certification::factory()->published()->create(), 'draft');

        $this->actingAs($coach)
            ->postJson(route('admin.sections.publish', $section))
            ->assertForbidden();
    }

    public function test_admin_can_destroy_draft_section(): void
    {
        $admin = User::factory()->admin()->create();
        [$part, $chapter, $section] = $this->makePartChain(Certification::factory()->published()->create(), 'draft');

        $this->actingAs($admin)
            ->delete(route('admin.sections.destroy', $section))
            ->assertRedirect(route('admin.chapters.show', $chapter));

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    public function test_assigned_coach_can_destroy_draft_section(): void
    {
        $coach = User::factory()->coach()->create();
        $cert = Certification::factory()->published()->create();
        $this->assignCoach($coach, $cert);
        [$part, $chapter, $section] = $this->makePartChain($cert, 'draft');

        $this->actingAs($coach)
            ->delete(route('admin.sections.destroy', $section))
            ->assertRedirect(route('admin.chapters.show', $chapter));

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }

    public function test_non_assigned_coach_cannot_destroy_section(): void
    {
        $coach = User::factory()->coach()->create();
        [$part, $chapter, $section] = $this->makePartChain(Certification::factory()->published()->create(), 'draft');

        $this->actingAs($coach)
            ->deleteJson(route('admin.sections.destroy', $section))
            ->assertForbidden();
    }

    public function test_preview_returns_html(): void
    {
        $admin = User::factory()->admin()->create();
        [$part, $chapter, $section] = $this->makePartChain(Certification::factory()->published()->create(), 'draft');

        $this->actingAs($admin)
            ->postJson(route('admin.sections.preview', $section), [
                'body' => "# タイトル\n\n本文",
            ])
            ->assertOk()
            ->assertJsonStructure(['html']);
    }
}
