<?php

namespace App\Http\Controllers;

use App\Services\TelegramPhoneVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramBotController extends Controller
{
    public function webhook(Request $request, TelegramPhoneVerifier $verifier): JsonResponse
    {
        $secret = config('services.telegram.webhook_secret');
        if ($secret && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secret) {
            return response()->json(['ok' => false, 'message' => 'Invalid webhook secret.'], 403);
        }

        $message = $request->input('message') ?: $request->input('edited_message');
        $chatId = data_get($message, 'chat.id');

        if (!$message || !$chatId) {
            return response()->json(['ok' => true]);
        }

        $text = trim((string) data_get($message, 'text', ''));
        if (str_starts_with($text, '/start')) {
            $this->sendPhoneRequest($chatId);

            return response()->json(['ok' => true]);
        }

        $contact = data_get($message, 'contact');
        if (!$contact) {
            $this->sendPhoneRequest($chatId);

            return response()->json(['ok' => true]);
        }

        $fromId = data_get($message, 'from.id');
        $contactUserId = data_get($contact, 'user_id');
        if ($fromId && $contactUserId && (string) $fromId !== (string) $contactUserId) {
            $this->sendMessage($chatId, 'Надішліть, будь ласка, власний номер телефону через кнопку нижче.');

            return response()->json(['ok' => true]);
        }

        $phone = $verifier->normalizePhone(data_get($contact, 'phone_number'));
        if (!$phone) {
            $this->sendMessage($chatId, 'Потрібен український номер у форматі +380XXXXXXXXX.');

            return response()->json(['ok' => true]);
        }

        $code = $verifier->issueCode($phone, $contactUserId ?: $fromId, $chatId);

        $this->sendMessage(
            $chatId,
            "Ваш код підтвердження: {$code}\n\nВін дійсний 10 хвилин. Введіть його у формі реєстрації.",
            ['remove_keyboard' => true]
        );

        return response()->json(['ok' => true]);
    }

    private function sendPhoneRequest(int|string $chatId): void
    {
        $this->sendMessage($chatId, 'Натисніть кнопку нижче, щоб отримати код підтвердження номера.', [
            'keyboard' => [[
                ['text' => 'Надіслати номер телефону', 'request_contact' => true],
            ]],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ]);
    }

    private function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null): void
    {
        $token = config('services.telegram.bot_token');
        if (!$token) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup) {
            $payload['reply_markup'] = $replyMarkup;
        }

        Http::asJson()->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
    }
}
