<?php

namespace App\Console\Commands;

use App\Services\ModerationNotifier;
use Illuminate\Console\Command;

class TestModerationNotify extends Command
{
    protected $signature = 'tg:notify-test';
    protected $description = 'Send a test moderation notification to the configured TG chat';

    public function handle(ModerationNotifier $notifier): int
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.moderation_chat_id');

        $this->line("TELEGRAM_BOT_TOKEN: " . ($token ? '✓ set (' . substr($token, 0, 8) . '…)' : '✗ EMPTY'));
        $this->line("TELEGRAM_MODERATION_CHAT_ID: " . ($chatId ?: '✗ EMPTY'));

        if (!$token || !$chatId) {
            $this->error('Missing config. Set both env vars and run `php artisan config:clear`.');
            return self::FAILURE;
        }

        $this->info('Sending test message...');
        $notifier->notifyPending('🧪 Тест', 'Перевірка моделі сповіщень', null, url('/admin'));
        $this->info('Done. Check Telegram chat ' . $chatId . ' and storage/logs/laravel.log for any warnings.');

        return self::SUCCESS;
    }
}
