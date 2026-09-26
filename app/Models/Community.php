<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\PurgesCompletely;
use Illuminate\Support\Facades\Storage;

class Community extends Model
{
    use PurgesCompletely;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_diaspora' => 'boolean', 'is_hidden' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function user(): BelongsTo
    {
        return $this->owner();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(CommunityMember::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_members')->withPivot('role', 'status')
            ->wherePivot('status', 'approved')->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function membershipOf(?User $user): ?CommunityMember
    {
        return $user ? $this->memberships()->where('user_id', $user->id)->first() : null;
    }

    public function isMember(?User $user): bool
    {
        return $user && $this->memberships()->where('user_id', $user->id)->where('status', 'approved')->exists();
    }

    public function isAdmin(?User $user): bool
    {
        return $user && ($user->id === $this->owner_id || $user->hasPermission('communities.manage')
            || $this->memberships()->where('user_id', $user->id)->where('status', 'approved')->where('role', 'admin')->exists());
    }

    public function isModerator(?User $user): bool
    {
        return $user && ($this->isAdmin($user)
            || $this->memberships()->where('user_id', $user->id)->where('status', 'approved')->where('role', 'moderator')->exists());
    }

    public function canView(?User $user): bool
    {
        return $this->visibility === 'public' || $this->isMember($user) || ($user && $user->isStaff());
    }

    public function avatarUrl(): string
    {
        return $this->avatar ? Storage::disk('public')->url($this->avatar)
            : 'https://ui-avatars.com/api/?background=0e7490&color=fff&bold=true&name='.urlencode($this->name);
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover) : null;
    }

    public function url(): string
    {
        return route('communities.show', $this->slug);
    }
}
