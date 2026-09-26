<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/** Notifications push (navigateur / mobile via service worker). */
class WebPushChannel
{
    public function send($notifiable, Notification $notification): void
    {
        $subs = $notifiable->pushSubscriptions;
        if ($subs->isEmpty() || ! method_exists($notification, 'toWebPush')) {
            return;
        }

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject' => config('vwajen.vapid.subject'),
                'publicKey' => config('vwajen.vapid.public'),
                'privateKey' => config('vwajen.vapid.private'),
            ]]);
            $payload = json_encode($notification->toWebPush($notifiable));

            foreach ($subs as $sub) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aes128gcm',
                ]), $payload);
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('WebPush failed', ['error' => $e->getMessage()]);
        }
    }
}
