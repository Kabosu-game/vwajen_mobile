<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Traitement vidéo via ffmpeg (si FFMPEG_PATH est configuré) :
 * compression H.264, qualités multiples (240p/360p/720p), miniature, durée.
 * Sans ffmpeg, la vidéo originale est servie telle quelle.
 */
class VideoProcessor
{
    public const RENDITIONS = [240 => 400, 360 => 800, 720 => 2500]; // hauteur => kbps

    public function available(): bool
    {
        $bin = config('vwajen.ffmpeg');

        return $bin && is_file($bin);
    }

    public function process(Video $video): void
    {
        if (! $this->available()) {
            $video->update(['processing_status' => 'ready']);

            return;
        }

        $video->update(['processing_status' => 'processing']);
        $disk = Storage::disk('public');
        $input = $disk->path($video->path);
        $dir = dirname($video->path);

        try {
            $meta = $this->probe($input);
            $updates = ['duration' => $meta['duration'] ?? null, 'width' => $meta['width'] ?? null, 'height' => $meta['height'] ?? null];

            if (! $video->thumbnail) {
                $thumb = $dir.'/'.Str::random(24).'.jpg';
                $this->run([config('vwajen.ffmpeg'), '-y', '-ss', '1', '-i', $input, '-frames:v', '1', '-vf', 'scale=640:-2', '-q:v', '4', $disk->path($thumb)]);
                if ($disk->exists($thumb)) {
                    $updates['thumbnail'] = $thumb;
                }
            }

            $sourceHeight = min($meta['width'] ?? 720, $meta['height'] ?? 720); // côté court (vertical ou horizontal)
            $qualities = [];
            foreach (self::RENDITIONS as $height => $kbps) {
                if ($height > $sourceHeight && $height !== 240) {
                    continue;
                }
                $out = $dir.'/'.Str::random(24)."_{$height}p.mp4";
                $scale = ($meta['height'] ?? 0) > ($meta['width'] ?? 0) ? "scale={$height}:-2" : "scale=-2:{$height}";
                $this->run([config('vwajen.ffmpeg'), '-y', '-i', $input, '-vf', $scale, '-c:v', 'libx264', '-preset', 'veryfast',
                    '-b:v', $kbps.'k', '-maxrate', ($kbps * 1.5).'k', '-bufsize', ($kbps * 2).'k', '-c:a', 'aac', '-b:a', '96k',
                    '-movflags', '+faststart', $disk->path($out)], 3600);
                if ($disk->exists($out)) {
                    $qualities[$height] = $out;
                }
            }

            $updates['qualities'] = $qualities ?: null;
            $updates['processing_status'] = 'ready';
            $video->update($updates);
        } catch (\Throwable $e) {
            Log::warning('Video processing failed', ['video' => $video->id, 'error' => $e->getMessage()]);
            $video->update(['processing_status' => 'ready']);
        }
    }

    public function probe(string $input): array
    {
        $probe = config('vwajen.ffprobe');
        if (! $probe || ! is_file($probe)) {
            return [];
        }
        $p = new Process([$probe, '-v', 'error', '-select_streams', 'v:0', '-show_entries', 'stream=width,height:format=duration', '-of', 'json', $input]);
        $p->run();
        $json = json_decode($p->getOutput(), true) ?: [];

        return [
            'width' => $json['streams'][0]['width'] ?? null,
            'height' => $json['streams'][0]['height'] ?? null,
            'duration' => isset($json['format']['duration']) ? (int) round($json['format']['duration']) : null,
        ];
    }

    /** Extrait la piste audio (pour la transcription automatique). */
    public function extractAudio(string $input): ?string
    {
        if (! $this->available()) {
            return null;
        }
        $out = storage_path('app/tmp/'.Str::random(20).'.mp3');
        @mkdir(dirname($out), 0775, true);
        $this->run([config('vwajen.ffmpeg'), '-y', '-i', $input, '-vn', '-ac', '1', '-ar', '16000', '-b:a', '48k', $out], 1800);

        return is_file($out) ? $out : null;
    }

    private function run(array $cmd, int $timeout = 600): void
    {
        $p = new Process($cmd);
        $p->setTimeout($timeout);
        $p->run();
    }
}
