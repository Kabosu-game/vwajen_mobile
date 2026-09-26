<?php

namespace App\Services;

use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Stockage et compression des médias (faible connexion : images redimensionnées et recompressées). */
class MediaService
{
    public const IMAGE_MIMES = 'jpg,jpeg,png,webp,gif';

    public const VIDEO_MIMES = 'mp4,webm,mov,m4v,3gp,mkv';

    /**
     * Compresse une image : redimensionne (côté max), convertit en JPEG/WebP qualité réduite.
     * Retourne le chemin sur le disque public.
     */
    public function storeImage(UploadedFile $file, string $dir, int $maxSize = 1600, int $quality = 80): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        if ($ext === 'gif' || ! function_exists('imagecreatefromstring')) {
            return $file->store($dir, 'public');
        }

        $src = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $src) {
            return $file->store($dir, 'public');
        }

        $src = $this->applyExifOrientation($src, $file->getRealPath());
        [$w, $h] = [imagesx($src), imagesy($src)];
        $ratio = min(1, $maxSize / max($w, $h));
        $nw = max(1, (int) round($w * $ratio));
        $nh = max(1, (int) round($h * $ratio));

        $dst = imagecreatetruecolor($nw, $nh);
        $hasAlpha = in_array($ext, ['png', 'webp'], true);
        if ($hasAlpha) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $useWebp = function_exists('imagewebp');
        $path = trim($dir, '/').'/'.Str::random(32).($useWebp ? '.webp' : '.jpg');
        ob_start();
        $useWebp ? imagewebp($dst, null, $quality) : imagejpeg($dst, null, $quality);
        Storage::disk('public')->put($path, ob_get_clean());
        imagedestroy($src);
        imagedestroy($dst);

        return $path;
    }

    private function applyExifOrientation($img, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $img;
        }
        $exif = @exif_read_data($path);
        $o = $exif['Orientation'] ?? 1;

        return match ((int) $o) {
            3 => imagerotate($img, 180, 0),
            6 => imagerotate($img, -90, 0),
            8 => imagerotate($img, 90, 0),
            default => $img,
        };
    }

    public function imageSize(string $path): array
    {
        $info = @getimagesize(Storage::disk('public')->path($path));

        return $info ? [$info[0], $info[1]] : [null, null];
    }

    public function storeFile(UploadedFile $file, string $dir, string $disk = 'public'): string
    {
        return $file->store($dir, $disk);
    }

    /** Déplace un fichier issu d'un upload fragmenté (reprise possible) vers le disque public. */
    public function adoptUpload(Upload $upload, string $dir): string
    {
        abort_unless($upload->status === 'complete', 422, __('Téléversement incomplet.'));
        $ext = strtolower(pathinfo($upload->filename, PATHINFO_EXTENSION)) ?: 'bin';
        $path = trim($dir, '/').'/'.Str::random(32).'.'.$ext;
        Storage::disk('public')->put($path, Storage::disk('local')->readStream($upload->path));
        Storage::disk('local')->delete($upload->path);
        $upload->update(['status' => 'used', 'path' => $path]);

        return $path;
    }

    public function delete(?string ...$paths): void
    {
        foreach (array_filter($paths) as $p) {
            if (! str_starts_with($p, 'http')) {
                Storage::disk('public')->delete($p);
            }
        }
    }
}
