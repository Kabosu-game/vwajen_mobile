<?php

use App\Models\Setting;
use App\Support\TextParser;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('media_url')) {
    function media_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
    }
}

if (! function_exists('short_number')) {
    function short_number(int|float|null $n): string
    {
        $n = (int) $n;

        return match (true) {
            $n >= 1_000_000 => round($n / 1_000_000, 1).'M',
            $n >= 1_000 => round($n / 1_000, 1).'k',
            default => (string) $n,
        };
    }
}

if (! function_exists('department_name')) {
    function department_name(?string $slug): ?string
    {
        return $slug ? (config("vwajen.departments.$slug.name") ?? $slug) : null;
    }
}

if (! function_exists('country_name')) {
    function country_name(?string $code): ?string
    {
        if (! $code) {
            return null;
        }
        if ($code === 'HT') {
            return __('Haïti');
        }
        if (class_exists(Locale::class)) {
            $name = Locale::getDisplayRegion('-'.$code, app()->getLocale() === 'ht' ? 'fr' : app()->getLocale());
            if ($name && $name !== $code) {
                return $name;
            }
        }

        return config("vwajen.diaspora_countries.$code", $code);
    }
}

if (! function_exists('position_name')) {
    function position_name(?string $key): ?string
    {
        return $key ? __(config("vwajen.positions.$key", $key)) : null;
    }
}

if (! function_exists('render_text')) {
    function render_text(?string $text): HtmlString
    {
        return TextParser::render($text);
    }
}

if (! function_exists('all_countries')) {
    /** Liste des pays (codes ISO) triée par nom. */
    function all_countries(): array
    {
        $codes = ['HT', 'US', 'CA', 'FR', 'DO', 'CL', 'BR', 'MX', 'BS', 'GF', 'GP', 'MQ', 'BE', 'CH', 'ES', 'TC', 'CU', 'VE', 'PA', 'DE', 'GB',
            'IT', 'PT', 'NL', 'AR', 'CO', 'EC', 'PE', 'JM', 'PR', 'VI', 'SX', 'MF', 'BL', 'CW', 'AW', 'TT', 'BB', 'LC', 'DM', 'AG', 'KN',
            'GD', 'VC', 'SR', 'GY', 'CR', 'NI', 'HN', 'GT', 'SV', 'BZ', 'UY', 'PY', 'BO', 'SN', 'CI', 'CM', 'CD', 'CG', 'BJ', 'TG', 'GA',
            'MA', 'DZ', 'TN', 'IL', 'AE', 'QA', 'CN', 'JP', 'KR', 'IN', 'AU', 'NZ', 'SE', 'NO', 'DK', 'FI', 'IE', 'AT', 'PL', 'RU', 'TR', 'ZA', 'NG', 'KE', 'GH'];
        $out = [];
        foreach ($codes as $c) {
            $out[$c] = country_name($c);
        }
        asort($out);

        return ['HT' => __('Haïti')] + $out;
    }
}
