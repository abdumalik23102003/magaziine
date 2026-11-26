<?php

namespace Tests\Feature;

use App\Filament\Partner\Resources\Brands\Pages\EditBrandProfile;
use App\Models\Brand;
use App\Models\User;
use App\Services\BrandStoryService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BrandStoryTest extends TestCase
{
    use RefreshDatabase;

    private User $partner;

    private Brand $own;

    private Brand $other;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->own = Brand::factory()->create(['slug' => 'own-farm']);
        $this->other = Brand::factory()->create(['slug' => 'rival-farm']);

        $this->partner = User::factory()->partner()->create();
        $this->partner->brands()->attach($this->own);

        Filament::setCurrentPanel(Filament::getPanel('partner'));
    }

    private function withVideo(Brand $brand, bool $enabled = true, string $name = 'story.mp4'): string
    {
        $path = app(BrandStoryService::class)->directory($brand).'/'.$name;
        Storage::disk('public')->put($path, 'video');
        $brand->update(['story_video_path' => $path, 'story_enabled' => $enabled]);

        return $path;
    }

    public function test_page_without_video_has_no_story_section(): void
    {
        $this->get('/brands/own-farm')
            ->assertOk()
            ->assertDontSee('data-brand-story', false)
            ->assertDontSee('ScrollTrigger', false);
    }

    public function test_enabled_video_is_shown_with_title_and_subtitle(): void
    {
        $path = $this->withVideo($this->own);
        $this->own->update(['story_title' => 'From seed to shelf', 'story_subtitle' => 'Grown slowly']);

        $this->get('/brands/own-farm')
            ->assertOk()
            ->assertSee('data-brand-story', false)
            ->assertSee($path, false)
            ->assertSee('From seed to shelf')
            ->assertSee('Grown slowly')
            ->assertSee('ScrollTrigger.min.js', false)
            ->assertSee('data-story-canvas', false)
            ->assertSee('brand-story-frames.js', false);
    }

    public function test_story_is_skipped_while_sorting_or_paginating(): void
    {
        $this->withVideo($this->own);

        $this->get('/brands/own-farm?sort=newest')->assertOk()->assertDontSee('data-brand-story', false);
        $this->get('/brands/own-farm?page=2')->assertOk()->assertDontSee('data-brand-story', false);
    }

    public function test_disabled_or_missing_video_is_hidden(): void
    {
        $path = $this->withVideo($this->own, false);

        $this->get('/brands/own-farm')->assertOk()->assertDontSee('data-brand-story', false);

        $this->own->update(['story_enabled' => true]);
        Storage::disk('public')->delete($path);

        $this->get('/brands/own-farm')->assertOk()->assertDontSee('data-brand-story', false);
    }

    public function test_partner_uploads_a_video_from_the_brand_profile(): void
    {
        $this->actingAs($this->partner);

        Livewire::test(EditBrandProfile::class, ['record' => $this->own->getRouteKey()])
            ->fillForm([
                'story_video_path' => [UploadedFile::fake()->create('clip.mp4', 1024, 'video/mp4')],
                'story_enabled' => true,
                'story_title' => 'Our story',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $own = $this->own->fresh();

        $this->assertTrue($own->story_enabled);
        $this->assertSame('Our story', $own->story_title);
        $this->assertStringStartsWith("brands/videos/{$own->id}/", $own->story_video_path);
        Storage::disk('public')->assertExists($own->story_video_path);
    }

    public function test_wrong_file_type_is_rejected(): void
    {
        $this->actingAs($this->partner);

        Livewire::test(EditBrandProfile::class, ['record' => $this->own->getRouteKey()])
            ->fillForm(['story_video_path' => [UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')]])
            ->call('save')
            ->assertHasFormErrors(['story_video_path']);

        $this->assertNull($this->own->fresh()->story_video_path);
    }

    public function test_replacing_and_removing_a_video_deletes_the_old_file(): void
    {
        $first = $this->withVideo($this->own, true, 'first.mp4');
        $second = $this->withVideo($this->own, true, 'second.mp4');

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        $this->own->update(['story_video_path' => null]);

        Storage::disk('public')->assertMissing($second);
    }

    public function test_deleting_a_brand_deletes_its_video(): void
    {
        $path = $this->withVideo($this->own);

        $this->own->delete();

        Storage::disk('public')->assertMissing($path);
    }

    public function test_another_brands_video_path_is_rejected(): void
    {
        $theirs = $this->withVideo($this->other);

        $this->expectException(AuthorizationException::class);

        app(BrandStoryService::class)->assertOwnPath($this->own, $theirs);
    }

    public function test_foreign_files_are_never_deleted(): void
    {
        $theirs = $this->withVideo($this->other);

        app(BrandStoryService::class)->delete($this->own, $theirs);

        Storage::disk('public')->assertExists($theirs);
    }

    public function test_partner_cannot_update_another_brand(): void
    {
        $this->assertTrue($this->partner->can('update', $this->own));
        $this->assertFalse($this->partner->can('update', $this->other));

        $this->actingAs($this->partner);

        $this->assertContains($this->get('/partner/brand-profile/rival-farm/edit')->status(), [403, 404]);
    }
}
