<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramVersion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class)->orderBy('position');
    }
}
