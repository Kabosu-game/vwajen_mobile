<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('position');
    }

    /** Nom dans la langue courante (ht / fr / en). */
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->{'name_'.$locale} ?? $this->name_fr;
    }

    public static function allActive()
    {
        return once(fn () => static::active()->get());
    }
}
