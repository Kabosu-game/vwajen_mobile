<?php

namespace App\Models;

use App\Support\Morph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Sanction extends Model
{
    public const TYPES = ['warning', 'suspension', 'ban', 'content_removal', 'content_hidden'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function content(): MorphTo
    {
        return $this->morphTo();
    }

    public function target(): ?Model
    {
        return $this->content_type ? Morph::find($this->content_type, $this->content_id, true) : null;
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(Appeal::class);
    }

    public function isActive(): bool
    {
        return ! $this->revoked_at && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public const TYPE_LABELS = [
        'warning' => 'Avertissement', 'suspension' => 'Suspension', 'ban' => 'Bannissement',
        'content_removal' => 'Suppression de contenu', 'content_hidden' => 'Contenu masqué',
    ];

    public function typeLabel(): string
    {
        return __(self::TYPE_LABELS[$this->type] ?? $this->type);
    }
}
