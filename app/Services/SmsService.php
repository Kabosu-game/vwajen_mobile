<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Envoi de SMS (vérification du téléphone, notifications SMS). Pilotes : log | twilio. */
class SmsService
{
    public function send(string $to, string $message): bool
    {
        $cfg = config('vwajen.sms');

        if ($cfg['driver'] === 'twilio' && $cfg['twilio_sid']) {
            $response = Http::asForm()->withBasicAuth($cfg['twilio_sid'], $cfg['twilio_token'])
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$cfg['twilio_sid']}/Messages.json", [
                    'To' => $to, 'From' => $cfg['twilio_from'], 'Body' => $message,
                ]);

            return $response->successful();
        }

        Log::info("[SMS] to {$to}: {$message}");

        return true;
    }
}
