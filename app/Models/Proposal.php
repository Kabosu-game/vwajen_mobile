<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Proposal extends Model
{
    protected $guarded = ['id'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ProgramVersion::class, 'program_version_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function sources(): MorphMany
    {
        return $this->morphMany(Source::class, 'sourceable');
    }

    public function url(): string
    {
        return route('programs.show', $this->version->program_id).'#proposal-'.$this->id;
    }
}
