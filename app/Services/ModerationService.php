<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Sanction;
use App\Models\Setting;
use App\Models\User;
use App\Support\Morph;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ModerationService
{
    public function __construct(private Notifier $notifier) {}

    public function createReport(?User $reporter, Model $target, string $reason, ?string $details): Report
    {
        $reportedUserId = $target instanceof User ? $target->id : ($target->user_id ?? $target->owner_id ?? null);

        $report = Report::create([
            'reporter_id' => $reporter?->id,
            'reportable_type' => $target->getMorphClass(),
            'reportable_id' => $target->getKey(),
            'reported_user_id' => $reportedUserId,
            'reason' => $reason,
            'details' => $details,
            'priority' => in_array($reason, ['violence', 'illegal', 'hate'], true) ? 'high' : 'normal',
        ]);

        // Modération assistée par IA : pré-évaluation pour prioriser la file (décision finale humaine).
        $ai = app(AiService::class);
        if ($ai->enabled()) {
            $text = $this->textOf($target);
            if ($text && ($assessment = $ai->assessReport($text, $reason))) {
                $report->update([
                    'ai_score' => $assessment['score'],
                    'ai_label' => $assessment['label'],
                    'priority' => $assessment['score'] >= 0.8 ? 'high' : $report->priority,
                ]);
            }
        }

        // Masquage automatique après N signalements distincts (paramétrable).
        $threshold = (int) Setting::get('auto_hide_threshold', 5);
        if ($threshold > 0 && isset($target->is_hidden) && ! $target->is_hidden) {
            $count = Report::where('reportable_type', $target->getMorphClass())->where('reportable_id', $target->getKey())
                ->where('status', 'pending')->distinct('reporter_id')->count('reporter_id');
            if ($count >= $threshold) {
                $target->forceFill(['is_hidden' => true])->save();
            }
        }

        return $report;
    }

    public function textOf(Model $m): ?string
    {
        return collect([$m->title ?? null, $m->body ?? null, $m->description ?? null, $m->bio ?? null, $m->name ?? null])
            ->filter()->implode("\n") ?: null;
    }

    public function hide(Model $content, User $moderator, string $reason, ?Report $report = null): void
    {
        $content->forceFill(['is_hidden' => true])->save();
        $this->sanction($this->ownerOf($content), $moderator, 'content_hidden', $reason, null, $content, $report);
        AuditLogger::log('content.hide', $content, ['reason' => $reason]);
    }

    public function restore(Model $content, User $moderator): void
    {
        if (method_exists($content, 'trashed') && $content->trashed()) {
            $content->restore();
        }
        if (array_key_exists('is_hidden', $content->getAttributes())) {
            $content->forceFill(['is_hidden' => false])->save();
        }
        Sanction::where('content_type', $content->getMorphClass())->where('content_id', $content->getKey())
            ->whereNull('revoked_at')->whereIn('type', ['content_hidden', 'content_removal'])
            ->update(['revoked_at' => now(), 'revoked_by' => $moderator->id]);
        AuditLogger::log('content.restore', $content);
    }

    public function remove(Model $content, User $moderator, string $reason, ?Report $report = null): void
    {
        $owner = $this->ownerOf($content);
        method_exists($content, 'trashed') ? $content->delete() : $content->forceFill(['is_hidden' => true])->save();
        $this->sanction($owner, $moderator, 'content_removal', $reason, null, $content, $report);
        AuditLogger::log('content.remove', $content, ['reason' => $reason]);
    }

    public function sanction(?User $user, User $moderator, string $type, string $reason, ?\DateTimeInterface $expires = null, ?Model $content = null, ?Report $report = null): ?Sanction
    {
        if (! $user) {
            return null;
        }

        return DB::transaction(function () use ($user, $moderator, $type, $reason, $expires, $content, $report) {
            $sanction = Sanction::create([
                'user_id' => $user->id,
                'moderator_id' => $moderator->id,
                'report_id' => $report?->id,
                'type' => $type,
                'content_type' => $content?->getMorphClass(),
                'content_id' => $content?->getKey(),
                'reason' => $reason,
                'expires_at' => $expires,
            ]);

            if ($type === 'suspension') {
                $user->forceFill(['status' => 'suspended', 'suspended_until' => $expires, 'status_reason' => $reason])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            } elseif ($type === 'ban') {
                $user->forceFill(['status' => 'banned', 'suspended_until' => null, 'status_reason' => $reason])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            AuditLogger::log('sanction.'.$type, $user, ['reason' => $reason, 'expires_at' => $expires?->format('c'), 'sanction_id' => $sanction->id]);

            $this->notifier->send($user, 'moderation', null, 'Mesure de modération : :type — :reason', [
                'type' => '__:'.(Sanction::TYPE_LABELS[$type] ?? $type), 'reason' => $reason,
            ], route('settings.sanctions'), $sanction);

            return $sanction;
        });
    }

    public function revoke(Sanction $sanction, User $moderator): void
    {
        $sanction->update(['revoked_at' => now(), 'revoked_by' => $moderator->id]);
        $user = $sanction->user;
        if (in_array($sanction->type, ['suspension', 'ban'], true) && $user) {
            $stillActive = $user->sanctions()->whereIn('type', ['suspension', 'ban'])->whereNull('revoked_at')
                ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
            if (! $stillActive) {
                $user->forceFill(['status' => 'active', 'suspended_until' => null, 'status_reason' => null])->save();
            }
        }
        if ($content = $sanction->target()) {
            if (in_array($sanction->type, ['content_hidden', 'content_removal'], true)) {
                $this->restore($content, $moderator);
            }
        }
        AuditLogger::log('sanction.revoke', $sanction);
    }

    public function ownerOf(Model $content): ?User
    {
        if ($content instanceof User) {
            return $content;
        }
        $id = $content->user_id ?? $content->owner_id ?? null;

        return $id ? User::withTrashed()->find($id) : null;
    }

    public static function targetLabel(Model $m): string
    {
        return Morph::label($m->getMorphClass()).' #'.$m->getKey();
    }
}
