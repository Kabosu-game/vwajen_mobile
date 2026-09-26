<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Hashtag extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime', 'is_blocked' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'name';
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'hashtaggable', 'hashtaggables');
    }

    public function videos(): MorphToMany
    {
        return $this->morphedByMany(Video::class, 'hashtaggable', 'hashtaggables');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hashtag_follows')->withTimestamps();
    }

    public function url(): string
    {
        return route('hashtags.show', $this->name);
    }
}
