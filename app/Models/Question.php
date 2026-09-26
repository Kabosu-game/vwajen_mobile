<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** « Kesyon pou kandida yo » */
class Question extends Model
{
    use Interactable, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime', 'closed_at' => 'datetime', 'is_hidden' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->where('is_hidden', false)->latest();
    }

    public function supporters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'question_supports')->withTimestamps();
    }

    public function isSupportedBy(?User $user): bool
    {
        return $user && $this->supporters()->where('user_id', $user->id)->exists();
    }

    /** Peut répondre : le candidat ciblé, ou tout candidat/élu vérifié si la question est publique. */
    public function canBeAnsweredBy(?User $user): bool
    {
        if (! $user || $this->status === 'closed') {
            return false;
        }
        if ($this->candidate_id) {
            return $user->id === $this->candidate_id;
        }

        return in_array($user->account_type, ['candidate', 'official'], true) && $user->is_verified;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'answered' => __('Répondue'),
            'closed' => __('Fermée'),
            default => __('Ouverte'),
        };
    }

    public function url(): string
    {
        return route('questions.show', $this->id);
    }
}
