<?php

namespace App\Notifications\Channels;

use App\Services\SmsService;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private SmsService $sms) {}

    public function send($notifiable, Notification $notification): void
    {
        if ($notifiable->phone && method_exists($notification, 'toSms')) {
            $this->sms->send($notifiable->phone, $notification->toSms($notifiable));
        }
    }
}
