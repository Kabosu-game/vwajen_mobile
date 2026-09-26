<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transcription automatique (lives, replays, vidéos, podcasts) et sous-titres automatiques.
 * Utilise un endpoint compatible Whisper (TRANSCRIPTION_URL) acceptant un fichier audio
 * et renvoyant du WebVTT (response_format=vtt).
 */
class TranscriptionService
{
    public function __construct(private VideoProcessor $video) {}

    public function enabled(): bool
    {
        return (bool) config('vwajen.transcription.url') && $this->video->available();
    }

    /** @return array{text: string, vtt: string}|null */
    public function transcribeFile(string $mediaPath, ?string $language = null): ?array
    {
        if (! $this->enabled()) {
            return null;
        }
        $audio = $this->video->extractAudio($mediaPath);
        if (! $audio) {
            return null;
        }

        try {
            $response = Http::timeout(900)
                ->withToken((string) config('vwajen.transcription.key'))
                ->attach('file', fopen($audio, 'r'), basename($audio))
                ->post(config('vwajen.transcription.url'), array_filter([
                    'model' => 'whisper-1',
                    'response_format' => 'vtt',
                    'language' => $language === 'ht' ? null : $language, // détection automatique pour le kreyòl
                ]));

            if (! $response->ok()) {
                Log::warning('Transcription failed', ['status' => $response->status()]);

                return null;
            }
            $vtt = $response->body();

            return ['vtt' => $vtt, 'text' => self::vttToText($vtt)];
        } catch (\Throwable $e) {
            Log::warning('Transcription failed', ['error' => $e->getMessage()]);

            return null;
        } finally {
            @unlink($audio);
        }
    }

    public static function vttToText(string $vtt): string
    {
        $lines = preg_split('/\R/', $vtt);
        $text = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line === 'WEBVTT' || ctype_digit($line) || str_contains($line, '-->')) {
                continue;
            }
            $text[] = $line;
        }

        return implode(' ', $text);
    }
}
