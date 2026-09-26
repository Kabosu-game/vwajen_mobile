<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class CandidateProfile extends Model
{
    /** Champs dont la modification est journalisée publiquement. */
    public const TRACKED = ['full_name', 'party', 'position_sought', 'constituency', 'department', 'election_year', 'biography', 'career'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sources(): MorphMany
    {
        return $this->morphMany(Source::class, 'sourceable');
    }

    public function photoUrl(): string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : $this->user->avatarUrl();
    }

    public function url(): string
    {
        return route('candidates.show', $this->user->username);
    }
}
