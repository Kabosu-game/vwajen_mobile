<?php

namespace App\Models;

use App\Support\Morph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    public const REASONS = ['spam', 'harassment', 'hate', 'violence', 'misinformation', 'impersonation', 'nudity', 'illegal', 'fake_account', 'other'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /** Contenu signalé, y compris s'il a été supprimé (pour restauration). */
    public function target(): ?Model
    {
        return Morph::find($this->reportable_type, $this->reportable_id, true);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public static function reasonLabel(string $reason): string
    {
        return match ($reason) {
            'spam' => __('Spam'),
            'harassment' => __('Harcèlement'),
            'hate' => __('Discours haineux'),
            'violence' => __('Violence ou menace'),
            'misinformation' => __('Fausse information'),
            'impersonation' => __('Usurpation d\'identité'),
            'nudity' => __('Nudité ou contenu sexuel'),
            'illegal' => __('Contenu illégal'),
            'fake_account' => __('Faux compte'),
            default => __('Autre'),
        };
    }
}
