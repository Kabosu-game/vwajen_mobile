<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ACCOUNT_TYPES = ['personal', 'candidate', 'organization', 'official'];

    public const NOTIFICATION_TYPES = [
        'follow', 'like', 'comment', 'repost', 'mention', 'question', 'candidate_answer', 'question_answered',
        'live', 'debate', 'event', 'system', 'moderation', 'message',
    ];

    protected $fillable = [
        'name', 'username', 'email', 'password', 'phone', 'account_type', 'avatar', 'cover', 'bio', 'website',
        'location', 'city', 'department', 'country', 'is_diaspora', 'locale', 'is_private', 'allow_messages',
        'allow_comments', 'allow_mentions', 'show_location', 'show_email', 'show_phone', 'show_political', 'searchable',
        'theme', 'font_size', 'high_contrast', 'data_saver', 'reduce_autoplay', 'reduce_motion', 'feed_mode',
        'interests', 'content_prefs', 'notification_prefs', 'cookie_consent_at', 'cookie_prefs', 'terms_accepted_at',
        'google_id', 'apple_id', 'registration_ip',
    ];

    protected $hidden = ['password', 'remember_token', 'phone_code', 'google_id', 'apple_id', 'registration_ip'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'phone_code_expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'verified_until' => 'datetime',
            'suspended_until' => 'datetime',
            'cookie_consent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_active_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'password' => 'hashed',
            'is_private' => 'boolean',
            'is_verified' => 'boolean',
            'is_diaspora' => 'boolean',
            'show_location' => 'boolean',
            'show_email' => 'boolean',
            'show_phone' => 'boolean',
            'show_political' => 'boolean',
            'searchable' => 'boolean',
            'high_contrast' => 'boolean',
            'data_saver' => 'boolean',
            'reduce_autoplay' => 'boolean',
            'reduce_motion' => 'boolean',
            'interests' => 'array',
            'content_prefs' => 'array',
            'notification_prefs' => 'array',
            'cookie_prefs' => 'array',
        ];
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: config('app.locale');
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }

    // ---------- Relations ----------

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function lives(): HasMany
    {
        return $this->hasMany(Live::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function questionsAsked(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function questionsReceived(): HasMany
    {
        return $this->hasMany(Question::class, 'candidate_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(Repost::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function candidateProfile(): HasOne
    {
        return $this->hasOne(CandidateProfile::class);
    }

    public function organizationProfile(): HasOne
    {
        return $this->hasOne(OrganizationProfile::class);
    }

    public function officialProfile(): HasOne
    {
        return $this->hasOne(OfficialProfile::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')
            ->wherePivot('status', 'accepted')->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')
            ->wherePivot('status', 'accepted')->withTimestamps();
    }

    public function followRequests(): HasMany
    {
        return $this->hasMany(Follow::class, 'following_id')->where('status', 'pending');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'blocker_id');
    }

    public function followedHashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'hashtag_follows')->withTimestamps();
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_members')
            ->withPivot('role', 'status')->wherePivot('status', 'approved')->withTimestamps();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(Sanction::class);
    }

    public function verificationRequests(): HasMany
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function publicRecords(): HasMany
    {
        return $this->hasMany(PublicRecord::class);
    }

    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class);
    }

    public function changeLogs()
    {
        return $this->morphMany(ChangeLog::class, 'subject');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // ---------- Rôles & permissions ----------

    public function hasRole(string ...$names): bool
    {
        return $this->roles->pluck('name')->intersect($names)->isNotEmpty();
    }

    public function permissionNames(): array
    {
        return once(fn () => $this->roles()->with('permissions')->get()
            ->flatMap(fn ($r) => $r->permissions->pluck('name'))->unique()->values()->all());
    }

    public function hasPermission(string $permission): bool
    {
        return $this->hasRole('superadmin') || in_array($permission, $this->permissionNames(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('superadmin', 'admin');
    }

    public function isStaff(): bool
    {
        return $this->hasRole('superadmin', 'admin', 'moderator', 'verifier');
    }

    // ---------- Graphe social ----------

    public function followingIds(): array
    {
        return once(fn () => Follow::where('follower_id', $this->id)->where('status', 'accepted')->pluck('following_id')->all());
    }

    public function blockedIdsBothWays(): array
    {
        return once(fn () => Block::where('blocker_id', $this->id)->pluck('blocked_id')
            ->merge(Block::where('blocked_id', $this->id)->pluck('blocker_id'))
            ->unique()->values()->all());
    }

    public function isFollowing(User $user): bool
    {
        return in_array($user->id, $this->followingIds(), true);
    }

    public function hasPendingFollow(User $user): bool
    {
        return Follow::where('follower_id', $this->id)->where('following_id', $user->id)->where('status', 'pending')->exists();
    }

    public function hasBlocked(User $user): bool
    {
        return Block::where('blocker_id', $this->id)->where('blocked_id', $user->id)->exists();
    }

    public function isBlockedBetween(User $user): bool
    {
        return in_array($user->id, $this->blockedIdsBothWays(), true);
    }

    /** Profil privé : seuls les abonnés acceptés voient le contenu. */
    public function canBeViewedBy(?User $viewer): bool
    {
        if ($viewer && ($viewer->id === $this->id || $viewer->isStaff())) {
            return true;
        }
        if ($viewer && $this->isBlockedBetween($viewer)) {
            return false;
        }
        if (in_array($this->account_type, ['candidate', 'organization', 'official'], true)) {
            return true; // les comptes publics ne peuvent pas être privés
        }

        return ! $this->is_private || ($viewer && $viewer->isFollowing($this));
    }

    public function allowsInteraction(string $setting, ?User $actor): bool
    {
        if (! $actor || $actor->id === $this->id) {
            return (bool) $actor;
        }
        if ($this->isBlockedBetween($actor)) {
            return false;
        }

        return match ($this->{$setting}) {
            'nobody' => false,
            'following' => $this->isFollowing($actor),
            default => true,
        };
    }

    // ---------- Statut ----------

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCandidate(): bool
    {
        return $this->account_type === 'candidate';
    }

    public function isVerifiedCandidate(): bool
    {
        return $this->isCandidate() && $this->is_verified;
    }

    /** Seuls les comptes certifiés peuvent faire des Lives. */
    public function canGoLive(): bool
    {
        return $this->is_verified && $this->isActive() && (! $this->verified_until || $this->verified_until->isFuture());
    }

    public function wantsNotification(string $type, string $channel = 'database'): bool
    {
        $prefs = $this->notification_prefs ?? [];
        $defaults = ['database' => true, 'mail' => false, 'push' => true, 'sms' => false];

        return (bool) ($prefs[$type][$channel] ?? $defaults[$channel] ?? false);
    }

    // ---------- Présentation ----------

    public function avatarUrl(): string
    {
        if ($this->avatar) {
            return str_starts_with($this->avatar, 'http') ? $this->avatar : Storage::disk('public')->url($this->avatar);
        }

        return 'https://ui-avatars.com/api/?background=1d4ed8&color=fff&bold=true&name='.urlencode($this->name);
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Storage::disk('public')->url($this->cover) : null;
    }

    public function profileUrl(): string
    {
        return route('profile.show', $this->username);
    }

    public function url(): string
    {
        return $this->profileUrl();
    }

    public function badgeLabel(): ?string
    {
        if (! $this->is_verified) {
            return null;
        }

        return match ($this->verified_type) {
            'candidate' => __('Candidat vérifié'),
            'organization' => __('Organisation vérifiée'),
            'official' => __('Responsable public vérifié'),
            default => __('Compte vérifié'),
        };
    }

    public function accountTypeLabel(): string
    {
        return match ($this->account_type) {
            'candidate' => __('Candidat'),
            'organization' => __('Organisation'),
            'official' => __('Responsable élu'),
            default => __('Citoyen'),
        };
    }

    public function unreadMessagesCount(): int
    {
        return ConversationParticipant::where('user_id', $this->id)->whereNull('left_at')
            ->get()->sum(fn ($p) => $p->unreadCount());
    }
}
