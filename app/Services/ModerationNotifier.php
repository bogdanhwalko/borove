<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModerationNotifier
{
    /**
     * Notify the moderation chat about a new pending item.
     * Errors are silently logged — never throws (to not break the user request).
     */
    public function notifyPending(string $kindLabel, string $title, ?User $author, string $reviewUrl): void
    {
        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.moderation_chat_id');

        if (!$token) {
            Log::warning('TG moderation notify skipped: TELEGRAM_BOT_TOKEN is empty');
            return;
        }
        if (!$chatId) {
            Log::warning('TG moderation notify skipped: TELEGRAM_MODERATION_CHAT_ID is empty');
            return;
        }

        $authorLine = $author
            ? trim(($author->last_name ?? '') . ' ' . ($author->first_name ?? '')) ?: ($author->nickname ?? ('#' . $author->id))
            : 'Невідомо';

        if ($author && $author->phone) {
            $authorLine .= ' · +380' . ltrim($author->phone, '0');
        }

        $text = "🔔 <b>Новий запис на модерації</b>\n"
              . "Тип: {$kindLabel}\n"
              . "Назва: " . $this->trim($title, 200) . "\n"
              . "Автор: {$authorLine}\n\n"
              . "<a href=\"{$reviewUrl}\">Відкрити у адмінці</a>";

        try {
            $response = Http::asJson()
                ->timeout(5)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if (!$response->successful()) {
                Log::warning('TG moderation notify HTTP ' . $response->status() . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::warning('TG moderation notify failed: ' . $e->getMessage());
        }
    }

    private function trim(string $s, int $max): string
    {
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max - 1) . '…' : $s;
    }
}
