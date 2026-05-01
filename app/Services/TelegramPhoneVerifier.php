<?php

namespace App\Services;

use App\Models\TelegramPhoneVerification;
use Illuminate\Support\Facades\Hash;

class TelegramPhoneVerifier
{
    private const CODE_TTL_MINUTES = 10;

    public function normalizePhone(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) === 12 && str_starts_with($digits, '380')) {
            $digits = '0' . substr($digits, 3);
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '80')) {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) === 9) {
            $digits = '0' . $digits;
        }

        return preg_match('/^0[0-9]{9}$/', $digits) ? $digits : null;
    }

    public function issueCode(string $phone, ?int $telegramUserId, int|string $telegramChatId): string
    {
        $normalizedPhone = $this->normalizePhone($phone);

        if (!$normalizedPhone) {
            throw new \InvalidArgumentException('Invalid phone number.');
        }

        TelegramPhoneVerification::where('phone', $normalizedPhone)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        TelegramPhoneVerification::create([
            'phone' => $normalizedPhone,
            'telegram_user_id' => $telegramUserId,
            'telegram_chat_id' => (string) $telegramChatId,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
        ]);

        return $code;
    }

    public function verify(string $phone, string $code): bool
    {
        $normalizedPhone = $this->normalizePhone($phone);
        $cleanCode = preg_replace('/\D/', '', $code);

        if (!$normalizedPhone || !preg_match('/^[0-9]{6}$/', $cleanCode)) {
            return false;
        }

        $verification = TelegramPhoneVerification::where('phone', $normalizedPhone)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$verification || !Hash::check($cleanCode, $verification->code_hash)) {
            return false;
        }

        $verification->update(['used_at' => now()]);

        return true;
    }
}
