<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Source extends Model
{
    public const STATUSES = ['provided', 'verified', 'unverified', 'disputed'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_on' => 'date', 'verified_at' => 'datetime', 'provided_by_candidate' => 'boolean'];
    }

    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(SourceVerification::class)->latest();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'verified' => __('Information vérifiée'),
            'provided' => __('Information fournie par le candidat'),
            'disputed' => __('Information contestée'),
            default => __('Information non vérifiée'),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'verified' => 'success',
            'provided' => 'info',
            'disputed' => 'danger',
            default => 'warning',
        };
    }
}
