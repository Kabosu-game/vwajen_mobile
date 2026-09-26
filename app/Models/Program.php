<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Program extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'archived_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProgramVersion::class)->orderByDesc('version_number');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ProgramVersion::class, 'current_version_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function sources(): MorphMany
    {
        return $this->morphMany(Source::class, 'sourceable');
    }

    public function changeLogs(): MorphMany
    {
        return $this->morphMany(ChangeLog::class, 'subject')->latest();
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function canManage(?User $user): bool
    {
        return $user && ($user->id === $this->user_id || $user->hasPermission('programs.manage'));
    }

    public function url(): string
    {
        return route('programs.show', $this->id);
    }
}
