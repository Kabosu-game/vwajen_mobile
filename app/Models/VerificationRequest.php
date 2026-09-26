<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VerificationRequest extends Model
{
    public const TYPES = ['candidate', 'organization', 'public', 'official'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'candidate' => __('Candidat'),
            'organization' => __('Organisation'),
            'official' => __('Responsable élu'),
            default => __('Compte public'),
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'approved' => __('Validée'),
            'rejected' => __('Rejetée'),
            'expired' => __('Expirée'),
            'cancelled' => __('Annulée'),
            default => __('En attente'),
        };
    }
}
