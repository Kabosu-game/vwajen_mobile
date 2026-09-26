<?php

return [
    'locales' => [
        'ht' => ['name' => 'Kreyòl ayisyen', 'short' => 'HT'],
        'fr' => ['name' => 'Français', 'short' => 'FR'],
        'en' => ['name' => 'English', 'short' => 'EN'],
    ],

    'departments' => [
        'ouest' => ['name' => 'Ouest', 'capital' => 'Port-au-Prince', 'lat' => 18.5392, 'lng' => -72.3350],
        'nord' => ['name' => 'Nord', 'capital' => 'Cap-Haïtien', 'lat' => 19.7592, 'lng' => -72.2014],
        'nord-est' => ['name' => 'Nord-Est', 'capital' => 'Fort-Liberté', 'lat' => 19.6667, 'lng' => -71.8333],
        'nord-ouest' => ['name' => 'Nord-Ouest', 'capital' => 'Port-de-Paix', 'lat' => 19.9397, 'lng' => -72.8307],
        'artibonite' => ['name' => 'Artibonite', 'capital' => 'Gonaïves', 'lat' => 19.4500, 'lng' => -72.6833],
        'centre' => ['name' => 'Centre', 'capital' => 'Hinche', 'lat' => 19.1500, 'lng' => -72.0167],
        'sud' => ['name' => 'Sud', 'capital' => 'Les Cayes', 'lat' => 18.2000, 'lng' => -73.7500],
        'sud-est' => ['name' => 'Sud-Est', 'capital' => 'Jacmel', 'lat' => 18.2342, 'lng' => -72.5347],
        'grand-anse' => ['name' => "Grand'Anse", 'capital' => 'Jérémie', 'lat' => 18.6500, 'lng' => -74.1167],
        'nippes' => ['name' => 'Nippes', 'capital' => 'Miragoâne', 'lat' => 18.4461, 'lng' => -73.0883],
    ],

    // Pays principaux de la diaspora haïtienne (Vwajèn Mond)
    'diaspora_countries' => [
        'US' => 'États-Unis', 'CA' => 'Canada', 'FR' => 'France', 'DO' => 'République dominicaine', 'CL' => 'Chili',
        'BR' => 'Brésil', 'MX' => 'Mexique', 'BS' => 'Bahamas', 'GF' => 'Guyane', 'GP' => 'Guadeloupe', 'MQ' => 'Martinique',
        'BE' => 'Belgique', 'CH' => 'Suisse', 'ES' => 'Espagne', 'TC' => 'Turks-et-Caïcos', 'CU' => 'Cuba', 'VE' => 'Venezuela',
        'PA' => 'Panama', 'DE' => 'Allemagne', 'GB' => 'Royaume-Uni',
    ],

    'positions' => [
        'president' => 'Président de la République', 'senator' => 'Sénateur', 'deputy' => 'Député',
        'mayor' => 'Maire / Cartel municipal', 'casec' => 'CASEC', 'asec' => 'ASEC', 'delegate' => 'Délégué de ville', 'other' => 'Autre',
    ],

    'limits' => [
        'post_length' => 1000,
        'comment_length' => 1000,
        'message_length' => 4000,
        'post_images' => 4,
        'image_kb' => 10240,
        'video_mb' => 1024,
        'short_seconds' => 180,
        'chunk_kb' => 2048,
    ],

    'verification_months' => 12,
    'account_purge_days' => 30,

    'ffmpeg' => env('FFMPEG_PATH'),
    'ffprobe' => env('FFPROBE_PATH'),

    'webrtc' => [
        'stun' => array_filter(explode(',', (string) env('WEBRTC_STUN', 'stun:stun.l.google.com:19302'))),
        'turn_url' => env('WEBRTC_TURN_URL'),
        'turn_username' => env('WEBRTC_TURN_USERNAME'),
        'turn_credential' => env('WEBRTC_TURN_CREDENTIAL'),
    ],

    'vapid' => [
        'public' => env('VAPID_PUBLIC_KEY'),
        'private' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@vwajen.ht'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'twilio_sid' => env('TWILIO_SID'),
        'twilio_token' => env('TWILIO_TOKEN'),
        'twilio_from' => env('TWILIO_FROM'),
    ],

    'ai' => [
        'key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),
    ],

    'transcription' => [
        'url' => env('TRANSCRIPTION_URL'),
        'key' => env('TRANSCRIPTION_KEY'),
    ],

    // Domaines d'e-mails jetables refusés (protection contre les faux comptes)
    'disposable_domains' => [
        'mailinator.com', 'guerrillamail.com', '10minutemail.com', 'tempmail.com', 'temp-mail.org', 'yopmail.com',
        'trashmail.com', 'sharklasers.com', 'getnada.com', 'throwawaymail.com', 'fakeinbox.com', 'dispostable.com',
        'maildrop.cc', 'mintemail.com', 'mohmal.com', 'emailondeck.com',
    ],
];
