<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('telegram:set-webhook {url?}', function () {
    $token = config('services.telegram.bot_token');
    if (!$token) {
        $this->error('TELEGRAM_BOT_TOKEN is not configured.');
        return 1;
    }

    $url = $this->argument('url') ?: rtrim(config('app.url'), '/') . '/api/telegram/webhook';
    $payload = ['url' => $url];

    if ($secret = config('services.telegram.webhook_secret')) {
        $payload['secret_token'] = $secret;
    }

    $response = Http::asJson()->post("https://api.telegram.org/bot{$token}/setWebhook", $payload);

    if (!$response->successful() || !$response->json('ok')) {
        $this->error($response->body());
        return 1;
    }

    $this->info("Telegram webhook set to {$url}");

    return 0;
})->purpose('Set Telegram bot webhook for phone verification');
