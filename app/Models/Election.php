<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'held_on' => 'date',
            'results_published' => 'boolean',
        ];
    }

    public function results(): HasMany
    {
        return $this->hasMany(ElectionResult::class);
    }
}
