<?php

namespace App\Console\Commands;

use App\Models\TelegramPhoneVerification;
use App\Services\TelegramPhoneVerifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class IssueTelegramCode extends Command
{
    protected $signature = 'tg:code {phone : Phone like 0971112233 or +380971112233} {--code= : Force this exact 6-digit code (dev only)}';

    protected $description = 'Issue a Telegram verification code without sending to Telegram (local dev)';

    public function handle(TelegramPhoneVerifier $verifier): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to run in production.');
            return self::FAILURE;
        }

        $phone = $verifier->normalizePhone($this->argument('phone'));
        if (!$phone) {
            $this->error('Bad phone format. Expected 10 digits like 0971112233.');
            return self::FAILURE;
        }

        $forced = $this->option('code');
        if ($forced && preg_match('/^[0-9]{6}$/', $forced)) {
            TelegramPhoneVerification::where('phone', $phone)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            TelegramPhoneVerification::create([
                'phone'            => $phone,
                'telegram_user_id' => null,
                'telegram_chat_id' => 'local-dev',
                'code_hash'        => Hash::make($forced),
                'expires_at'       => now()->addMinutes(10),
            ]);

            $code = $forced;
        } else {
            $code = $verifier->issueCode($phone, null, 'local-dev');
        }

        $this->info("Phone: $phone");
        $this->info("Code:  $code");
        $this->line('Valid for 10 minutes. Use this code in the registration form.');

        return self::SUCCESS;
    }
}
