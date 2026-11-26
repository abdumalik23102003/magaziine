set -euo pipefail

if [ ! -f artisan ] || [ ! -f app/Observers/BrandObserver.php ]; then
  echo "Skriptni magaziine loyihasi ildizida ishga tushiring." >&2
  exit 1
fi

if [ -f app/Services/BrandStoryService.php ]; then
  echo "A qism allaqachon qo'llangan." >&2
  exit 1
fi

patch_file() {
  OLD="$2" NEW="$3" perl -0pi -e '$found ||= s/\Q$ENV{OLD}\E/$ENV{NEW}/; END { die "Anchor topilmadi\n" unless $found }' "$1" || {
    echo "Xato: $1 ichida anchor topilmadi: $2" >&2
    exit 1
  }
}

mkdir -p resources/views/filament/partner

cat > database/migrations/2026_11_28_100000_add_story_video_to_brands_table.php <<'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('story_video_path')->nullable();
            $table->boolean('story_enabled')->default(false);
            $table->string('story_title', 120)->nullable();
            $table->string('story_subtitle', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['story_video_path', 'story_enabled', 'story_title', 'story_subtitle']);
        });
    }
};
EOF

cat > app/Services/BrandStoryService.php <<'EOF'
<?php

namespace App\Services;

use App\Models\Brand;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;

/** Brendning scroll-story videosi: saqlash joyi, egalik tekshiruvi, o'chirish va public sahifa ma'lumoti. */
class BrandStoryService
{
    public const MAX_KB = 51200;

    public const MIME_TYPES = ['video/mp4', 'video/webm'];

    /** Har bir brendning o'z papkasi. */
    public function directory(Brand $brand): string
    {
        return 'brands/videos/'.$brand->getKey();
    }

    public function owns(Brand $brand, ?string $path): bool
    {
        return $path !== null
            && str_starts_with($path, $this->directory($brand).'/')
            && ! str_contains($path, '..');
    }

    /** Forma orqali boshqa brend fayliga yo'l yuborilishini rad etadi. */
    public function assertOwnPath(Brand $brand, mixed $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (! is_string($path) || ! $this->owns($brand, $path)) {
            throw new AuthorizationException('This video does not belong to your brand.');
        }
    }

    /** Faqat shu brend papkasidagi fayl o'chadi. */
    public function delete(Brand $brand, ?string $path): void
    {
        if ($this->owns($brand, $path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Public sahifa uchun. null = bo'lim ko'rsatilmaydi (o'chirilgan, video yo'q yoki fayl diskda yo'q).
     *
     * @return array{video_url:string, poster_url:?string, title:?string, subtitle:?string}|null
     */
    public function forPage(Brand $brand): ?array
    {
        $path = $brand->story_video_path;

        if (! $brand->story_enabled || ! $this->owns($brand, $path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return [
            'video_url' => Brand::mediaUrl($path),
            'poster_url' => $brand->cover_url,
            'title' => $brand->story_title ?: null,
            'subtitle' => $brand->story_subtitle ?: null,
        ];
    }
}
EOF

cat > resources/views/filament/partner/story-preview.blade.php <<'EOF'
@php
    $record = $getRecord();
    $url = \App\Models\Brand::mediaUrl($record?->story_video_path);
@endphp

<div>
    @if ($url)
        <p style="margin-bottom: .5rem; font-size: .875rem; font-weight: 500;">Current video</p>
        <video wire:key="story-preview-{{ md5($url) }}" src="{{ $url }}" controls muted playsinline preload="metadata" style="width: 100%; max-height: 360px; border-radius: .75rem; background: #000;"></video>
    @else
        <p style="font-size: .875rem; opacity: .7;">No video uploaded yet.</p>
    @endif
</div>
EOF

patch_file app/Models/Brand.php "        'sort_order',
    ];" "        'sort_order',
        'story_video_path',
        'story_enabled',
        'story_title',
        'story_subtitle',
    ];"

patch_file app/Models/Brand.php "            'is_active' => 'boolean',
" "            'is_active' => 'boolean',
            'story_enabled' => 'boolean',
"

patch_file app/Observers/BrandObserver.php 'use App\Services\BrandService;
' 'use App\Services\BrandService;
use App\Services\BrandStoryService;
'

patch_file app/Observers/BrandObserver.php 'public function __construct(private BrandService $brands) {}' 'public function __construct(private BrandService $brands, private BrandStoryService $stories) {}'

patch_file app/Observers/BrandObserver.php "    /** Rasm almashtirilsa, eskisi diskdan o'chadi. */" "    /** Rasm yoki video almashtirilsa, eskisi diskdan o'chadi. */"

patch_file app/Observers/BrandObserver.php '    public function deleted(Brand $brand): void
    {' '    public function deleted(Brand $brand): void
    {
        $this->stories->delete($brand, $brand->story_video_path);'

patch_file app/Observers/BrandObserver.php "        foreach (['logo_path', 'cover_path'] as \$column) {" "        if (\$brand->wasChanged('story_video_path')) {
            \$this->stories->delete(\$brand, \$brand->getOriginal('story_video_path'));
        }

        foreach (['logo_path', 'cover_path'] as \$column) {"

FORM=app/Filament/Partner/Resources/Brands/Schemas/BrandProfileForm.php

patch_file "$FORM" 'use Filament\Forms\Components\FileUpload;
' 'use App\Models\Brand;
use App\Services\BrandStoryService;
use Filament\Forms\Components\FileUpload;
'

patch_file "$FORM" 'use Filament\Forms\Components\TextInput;
' 'use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
'

patch_file "$FORM" "                    ->maxSize(4096),
" "                    ->maxSize(4096),
                Section::make('Story video')
                    ->description('A scroll-controlled video at the top of your public brand page.')
                    ->columnSpanFull()
                    ->schema([
                        View::make('filament.partner.story-preview'),
                        FileUpload::make('story_video_path')
                            ->label('Video (MP4 or WebM)')
                            ->disk('public')
                            ->directory(fn (Brand \$record): string => app(BrandStoryService::class)->directory(\$record))
                            ->visibility('public')
                            ->acceptedFileTypes(BrandStoryService::MIME_TYPES)
                            ->maxSize(BrandStoryService::MAX_KB)
                            ->previewable(false)
                            ->helperText('Up to 50 MB. Best results: 5-15 seconds, 1280-1920px wide, H.264 MP4 with frequent keyframes. Remove the file and save to delete it.'),
                        Toggle::make('story_enabled')
                            ->label('Show the scroll animation on the public page'),
                        TextInput::make('story_title')
                            ->label('Title over the video')
                            ->maxLength(120),
                        TextInput::make('story_subtitle')
                            ->label('Subtitle over the video')
                            ->maxLength(255),
                    ]),
"

PAGE=app/Filament/Partner/Resources/Brands/Pages/EditBrandProfile.php

patch_file "$PAGE" 'use App\Filament\Partner\Resources\Brands\BrandProfileResource;
' 'use App\Filament\Partner\Resources\Brands\BrandProfileResource;
use App\Services\BrandStoryService;
'

patch_file "$PAGE" "        \$record->update(Arr::only(\$data, ['logo_path', 'cover_path', 'tagline', 'description', 'website']));" "        \$data = Arr::only(\$data, [
            'logo_path', 'cover_path', 'tagline', 'description', 'website',
            'story_video_path', 'story_enabled', 'story_title', 'story_subtitle',
        ]);

        app(BrandStoryService::class)->assertOwnPath(\$record, \$data['story_video_path'] ?? null);

        \$record->update(\$data);"

patch_file app/Providers/AppServiceProvider.php 'use App\Observers\PartnerChangeObserver;
' 'use App\Observers\PartnerChangeObserver;
use App\Services\BrandStoryService;
'

patch_file app/Providers/AppServiceProvider.php "        Paginator::defaultView('vendor.pagination.neon');
" "        Paginator::defaultView('vendor.pagination.neon');

        // Livewire vaqtinchalik yuklash limiti standart 12 MB: story video uchun oshiriladi.
        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:'.BrandStoryService::MAX_KB]]);
"

echo "A qism tayyor."
