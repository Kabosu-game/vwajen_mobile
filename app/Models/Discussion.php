<?php

namespace App\Models;

use App\Models\Concerns\Interactable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discussion extends Model
{
    use Interactable, SoftDeletes;

    protected $table = 'community_discussions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_pinned' => 'boolean', 'is_locked' => 'boolean', 'is_hidden' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function url(): string
    {
        return route('discussions.show', [$this->community->slug, $this->id]);
    }
}
