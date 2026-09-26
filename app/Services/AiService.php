<?php

namespace App\Services;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIStatusException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Fonctionnalités assistées par IA (Claude) :
 * traduction automatique, résumés de débats, modération assistée, recherche intelligente.
 * Tout est optionnel : sans ANTHROPIC_API_KEY, les méthodes renvoient null / des valeurs neutres.
 */
class AiService
{
    private ?Client $client = null;

    public function enabled(): bool
    {
        return (bool) config('vwajen.ai.key');
    }

    private function client(): Client
    {
        return $this->client ??= new Client(apiKey: config('vwajen.ai.key'));
    }

    /** Appel simple ; renvoie le texte de la réponse ou null (désactivé, refus, erreur). */
    public function complete(string $system, string $prompt, int $maxTokens = 16000): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $message = $this->client()->messages->create(
                model: config('vwajen.ai.model'),
                maxTokens: $maxTokens,
                system: $system,
                messages: [['role' => 'user', 'content' => $prompt]],
            );

            if ($message->stopReason === 'refusal') {
                return null;
            }

            $text = '';
            foreach ($message->content as $block) {
                if ($block->type === 'text') {
                    $text .= $block->text;
                }
            }

            return trim($text) ?: null;
        } catch (APIStatusException $e) {
            Log::warning('AI request failed', ['type' => $e->type?->value, 'message' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::warning('AI request failed', ['message' => $e->getMessage()]);
        }

        return null;
    }

    public function translate(string $text, string $targetLocale): ?string
    {
        $language = ['ht' => 'Haitian Creole (Kreyòl ayisyen)', 'fr' => 'French', 'en' => 'English'][$targetLocale] ?? 'French';

        return Cache::remember('ai.tr.'.$targetLocale.'.'.md5($text), now()->addDays(30), fn () => $this->complete(
            "You are a translator for Vwajèn, a Haitian civic social network. Translate the user's text into {$language}. "
            .'Keep names, @mentions, #hashtags and URLs unchanged. Output only the translation, with no preamble.',
            $text,
            4000,
        ));
    }

    /** Résumé neutre d'un débat (sans prise de position ni classement des candidats). */
    public function summarizeDebate(string $title, array $participants, string $material, string $locale): ?string
    {
        $language = ['ht' => 'Haitian Creole', 'fr' => 'French', 'en' => 'English'][$locale] ?? 'French';

        return $this->complete(
            "You write neutral, factual summaries of political debates for Vwajèn, a Haitian civic platform. Write in {$language}. "
            .'Summarize what each participant said on each topic, in the order topics came up. Do not rank participants, declare a winner, '
            .'judge arguments, or add information that is not in the material. If the material is thin, say so briefly. Use short sections with plain headings.',
            "Debate: {$title}\nParticipants: ".implode(', ', $participants)."\n\nMaterial (transcript, public questions, selected chat):\n{$material}",
        );
    }

    /**
     * Pré-analyse d'un contenu signalé pour prioriser la file de modération.
     * La décision finale reste humaine.
     *
     * @return array{score: float, label: string}|null
     */
    public function assessReport(string $content, string $reason): ?array
    {
        $raw = $this->complete(
            'You help human moderators of Vwajèn, a Haitian civic social network (content in Haitian Creole, French or English), '
            .'prioritize their queue. Assess how likely the content violates community rules (harassment, hate, violence or threats, '
            .'spam, impersonation, sexual content, illegal content, clearly false civic information such as wrong election dates). '
            .'Political criticism and strong opinions are allowed. Reply with a single JSON object and nothing else: '
            .'{"score": number between 0 and 1, "label": one of "ok","spam","harassment","hate","violence","misinformation","sexual","illegal","other"}.',
            "Reason given by the reporter: {$reason}\n\nContent:\n".mb_substr($content, 0, 6000),
            1000,
        );
        if (! $raw || ! preg_match('/\{.*\}/s', $raw, $m)) {
            return null;
        }
        $data = json_decode($m[0], true);
        if (! is_array($data) || ! isset($data['score'])) {
            return null;
        }

        return ['score' => max(0, min(1, (float) $data['score'])), 'label' => (string) ($data['label'] ?? 'other')];
    }

    /** Recherche intelligente : termes équivalents en kreyòl, français et anglais. */
    public function expandSearch(string $query): array
    {
        if (! $this->enabled() || mb_strlen($query) < 3) {
            return [];
        }

        return Cache::remember('ai.search.'.md5(mb_strtolower($query)), now()->addDays(7), function () use ($query) {
            $raw = $this->complete(
                'You expand search queries for Vwajèn, a Haitian civic social network. Given a query, return up to 6 short alternative '
                .'search terms (synonyms and translations across Haitian Creole, French and English). Reply with a JSON array of strings only.',
                $query,
                500,
            );
            if (! $raw || ! preg_match('/\[.*\]/s', $raw, $m)) {
                return [];
            }

            return array_slice(array_values(array_filter((array) json_decode($m[0], true), 'is_string')), 0, 6);
        });
    }

    /** Traduit un fichier de sous-titres WebVTT en conservant les horodatages. */
    public function translateVtt(string $vtt, string $targetLocale): ?string
    {
        $language = ['ht' => 'Haitian Creole', 'fr' => 'French', 'en' => 'English'][$targetLocale] ?? 'French';
        $out = $this->complete(
            "Translate the cue text of this WebVTT file into {$language}. Keep the WEBVTT header, cue numbers and timestamps exactly as they are. Output only the file.",
            $vtt,
        );

        return $out && str_starts_with(ltrim($out), 'WEBVTT') ? $out : null;
    }
}
