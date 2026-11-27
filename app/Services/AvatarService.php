<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Profil rasmi: yuklangan fayl markazdan kvadrat qilib kesiladi, 256px ga kichraytiriladi va qayta kodlanadi
 * (EXIF va yashirin ma'lumotlar tushib qoladi). GD bo'lmasa asl fayl o'zgarishsiz saqlanadi.
 */
class AvatarService
{
    public const DISK = 'public';

    public const SIZE = 256;

    public const MAX_KB = 4096;

    public function store(User $user, UploadedFile $file): string
    {
        $contents = (string) file_get_contents($file->getRealPath());
        [$bytes, $ext] = $this->reencode($contents) ?? [$contents, $this->safeExtension($file)];

        $path = 'avatars/'.$user->id.'-'.Str::lower(Str::random(16)).'.'.$ext;
        Storage::disk(self::DISK)->put($path, $bytes);

        $old = $user->avatar_path;
        $user->forceFill(['avatar_path' => $path])->save();

        if ($old && $old !== $path) {
            Storage::disk(self::DISK)->delete($old);
        }

        return $path;
    }

    public function remove(User $user): void
    {
        if ($user->avatar_path) {
            Storage::disk(self::DISK)->delete($user->avatar_path);
        }

        $user->forceFill(['avatar_path' => null])->save();
    }

    /** @return array{0: string, 1: string}|null [bytes, extension] */
    private function reencode(string $contents): ?array
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagecreatetruecolor')) {
            return null;
        }

        $src = @imagecreatefromstring($contents);
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $side = min($w, $h);

        $dst = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 255, 255, 255, 127));
        imagecopyresampled($dst, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), self::SIZE, self::SIZE, $side, $side);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($dst, null, 85);
            $ext = 'webp';
        } else {
            imagepng($dst);
            $ext = 'png';
        }
        $bytes = (string) ob_get_clean();

        return $bytes === '' ? null : [$bytes, $ext];
    }

    private function safeExtension(UploadedFile $file): string
    {
        $ext = strtolower((string) $file->guessExtension());

        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';
    }
}
