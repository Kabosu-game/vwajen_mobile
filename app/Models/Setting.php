<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Paramètres de la plateforme modifiables depuis l'administration. */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public static function allCached(): array
    {
        try {
            return Cache::rememberForever('settings.all', fn () => static::all()->mapWithKeys(fn ($s) => [$s->key => $s->castValue()])->all());
        } catch (\Throwable) {
            return [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::allCached()[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(['key' => $key], [
            'value' => is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : $value),
            'type' => $type, 'group' => $group,
        ]);
        Cache::forget('settings.all');
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'bool' => $this->value === '1',
            'int' => (int) $this->value,
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }
}
