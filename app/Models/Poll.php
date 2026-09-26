<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Poll extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['ends_at' => 'datetime', 'multiple' => 'boolean'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(PollOption::class)->orderBy('position');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PollVote::class);
    }

    public function isOpen(): bool
    {
        return ! $this->ends_at || $this->ends_at->isFuture();
    }

    public function votedOptionIds(?User $user): array
    {
        return $user ? $this->votes()->where('user_id', $user->id)->pluck('poll_option_id')->all() : [];
    }

    public function percent(PollOption $option): int
    {
        $total = $this->options->sum('votes_count');

        return $total ? (int) round($option->votes_count * 100 / $total) : 0;
    }
}
