<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

/** Analyse et rendu sécurisé du texte : liens, #hashtags, @mentions. */
class TextParser
{
    public const HASHTAG_RE = '/(?<![\w&])#([\p{L}\p{N}_]{2,100})/u';

    public const MENTION_RE = '/(?<![\w@])@([A-Za-z0-9_]{3,30})/';

    public const URL_RE = '~\bhttps?://[^\s<>"\']+~i';

    public static function hashtags(?string $text): array
    {
        preg_match_all(self::HASHTAG_RE, (string) $text, $m);

        return array_values(array_unique(array_map(fn ($t) => mb_strtolower($t), $m[1])));
    }

    public static function mentions(?string $text): array
    {
        preg_match_all(self::MENTION_RE, (string) $text, $m);

        return array_values(array_unique(array_map('strtolower', $m[1])));
    }

    public static function firstUrl(?string $text): ?string
    {
        return preg_match(self::URL_RE, (string) $text, $m) ? rtrim($m[0], '.,;:!?)') : null;
    }

    public static function render(?string $text): HtmlString
    {
        $html = e((string) $text);

        $html = preg_replace_callback(self::URL_RE, function ($m) {
            $url = rtrim($m[0], '.,;:!?)');
            $tail = substr($m[0], strlen($url));
            $label = mb_strimwidth(preg_replace('~^https?://(www\.)?~', '', $url), 0, 45, '…');

            return '<a href="'.$url.'" target="_blank" rel="noopener nofollow ugc" class="link">'.$label.'</a>'.$tail;
        }, $html);

        $html = preg_replace_callback(self::HASHTAG_RE, fn ($m) => '<a href="'.route('hashtags.show', mb_strtolower($m[1])).'" class="hashtag">#'.$m[1].'</a>', $html);
        $html = preg_replace_callback(self::MENTION_RE, fn ($m) => '<a href="'.url('/@'.$m[1]).'" class="mention">@'.$m[1].'</a>', $html);

        return new HtmlString(nl2br($html));
    }

    /** Aperçu d'un lien (titre, description, image) via les balises Open Graph. */
    public static function linkPreview(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        $ip = $host ? gethostbyname($host) : null;
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return []; // protection SSRF : pas d'adresses internes
        }

        try {
            $response = Http::timeout(4)->withoutRedirecting()->withHeaders(['User-Agent' => 'VwajenBot/1.0'])->get($url);
            if (! $response->ok() || ! str_contains($response->header('Content-Type'), 'html')) {
                return [];
            }
            $html = substr($response->body(), 0, 200000);
            $get = function (string $prop) use ($html) {
                if (preg_match('~<meta[^>]+(?:property|name)=["\']'.preg_quote($prop, '~').'["\'][^>]+content=["\']([^"\']*)~i', $html, $m)) {
                    return html_entity_decode($m[1]);
                }
                if (preg_match('~<meta[^>]+content=["\']([^"\']*)["\'][^>]+(?:property|name)=["\']'.preg_quote($prop, '~').'["\']~i', $html, $m)) {
                    return html_entity_decode($m[1]);
                }

                return null;
            };
            $title = $get('og:title') ?? (preg_match('~<title>([^<]*)</title>~i', $html, $m) ? html_entity_decode(trim($m[1])) : null);

            return array_filter([
                'link_title' => $title ? mb_substr($title, 0, 250) : null,
                'link_description' => ($d = $get('og:description') ?? $get('description')) ? mb_substr($d, 0, 490) : null,
                'link_image' => ($i = $get('og:image')) && str_starts_with($i, 'http') ? $i : null,
            ]);
        } catch (\Throwable) {
            return [];
        }
    }
}
