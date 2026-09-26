<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification générique. Le texte est stocké sous forme de clé de traduction + paramètres
 * et traduit à l'affichage dans la langue de l'utilisateur (ht / fr / en).
 */
class ActivityNotification extends Notification
{
    /** Types toujours délivrés dans l'application (non désactivables). */
    public const FORCED = ['system', 'moderation'];

    public function __construct(
        public string $type,
        public ?User $actor,
        public string $textKey,
        public array $params = [],
        public ?string $link = null,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
    ) {}

    public function via(User $notifiable): array
    {
        $channels = [];
        if (in_array($this->type, self::FORCED, true) || $notifiable->wantsNotification($this->type, 'database')) {
            $channels[] = 'database';
        }
        if ($notifiable->email_verified_at && $notifiable->wantsNotification($this->type, 'mail')) {
            $channels[] = 'mail';
        }
        if (config('vwajen.vapid.public') && $notifiable->wantsNotification($this->type, 'push')) {
            $channels[] = WebPushChannel::class;
        }
        if ($notifiable->phone_verified_at && $notifiable->wantsNotification($this->type, 'sms')) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function text(): string
    {
        return self::render($this->textKey, $this->params);
    }

    /** Traduit la clé et les paramètres préfixés par « __: » dans la langue courante. */
    public static function render(string $key, array $params = []): string
    {
        $params = array_map(fn ($v) => is_string($v) && str_starts_with($v, '__:') ? __(substr($v, 3)) : $v, $params);

        return __($key, $params);
    }

    public function toArray(User $notifiable): array
    {
        return [
            'type' => $this->type,
            'text_key' => $this->textKey,
            'params' => $this->params,
            'url' => $this->link,
            'actor_id' => $this->actor?->id,
            'actor_name' => $this->actor?->name,
            'actor_username' => $this->actor?->username,
            'actor_avatar' => $this->actor?->avatarUrl(),
            'subject_type' => $this->subjectType,
            'subject_id' => $this->subjectId,
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Vwajèn').' — '.$this->text())
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line($this->text())
            ->action(__('Voir sur Vwajèn'), $this->link ?? url('/'))
            ->line(__('Vous pouvez gérer vos notifications par e-mail dans vos paramètres.'));
    }

    public function toWebPush(User $notifiable): array
    {
        return [
            'title' => 'Vwajèn',
            'body' => $this->text(),
            'url' => $this->link ?? url('/'),
            'icon' => asset('images/icon-192.png'),
        ];
    }

    public function toSms(User $notifiable): string
    {
        return 'Vwajèn: '.$this->text().' '.($this->link ?? '');
    }
}
