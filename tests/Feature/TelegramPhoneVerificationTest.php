<?php

namespace Tests\Feature;

use App\Models\TelegramPhoneVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_telegram_webhook_issues_code_for_users_own_contact(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00'));
        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 67890],
                'contact' => [
                    'user_id' => 67890,
                    'phone_number' => '+380671112233',
                ],
            ],
        ])->assertOk();

        $verification = TelegramPhoneVerification::first();
        $this->assertNotNull($verification);
        $this->assertSame('0671112233', $verification->phone);
        $this->assertSame(67890, $verification->telegram_user_id);
        $this->assertSame('12345', $verification->telegram_chat_id);
        $this->assertTrue($verification->expires_at->equalTo(Carbon::parse('2026-05-01 10:10:00')));
        $this->assertNull($verification->used_at);

        Http::assertSent(fn ($request) =>
            str_contains($request->url(), '/bottest-token/sendMessage')
            && $request['chat_id'] === 12345
            && str_contains($request['text'], 'Ваш код підтвердження:')
        );
    }

    public function test_telegram_webhook_rejects_someone_elses_contact(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake([
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->postJson('/api/telegram/webhook', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 111],
                'contact' => [
                    'user_id' => 222,
                    'phone_number' => '+380671112233',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseCount('telegram_phone_verifications', 0);

        Http::assertSent(fn ($request) =>
            $request['chat_id'] === 12345
            && str_contains($request['text'], 'власний номер телефону')
        );
    }

    public function test_register_requires_valid_telegram_code_for_phone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-01 10:00:00'));

        $verification = TelegramPhoneVerification::create([
            'phone' => '0671112233',
            'telegram_user_id' => 67890,
            'telegram_chat_id' => '12345',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/register', $this->registrationPayload([
            'phone' => '0671112233',
            'telegram_code' => '123456',
        ]))
            ->assertCreated()
            ->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', ['phone' => '0671112233']);
        $this->assertNotNull($verification->fresh()->used_at);
    }

    public function test_register_rejects_invalid_telegram_code(): void
    {
        TelegramPhoneVerification::create([
            'phone' => '0671112233',
            'telegram_user_id' => 67890,
            'telegram_chat_id' => '12345',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/register', $this->registrationPayload([
            'phone' => '0671112233',
            'telegram_code' => '000000',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['telegram_code']);

        $this->assertDatabaseMissing('users', ['phone' => '0671112233']);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Test',
            'first_name' => 'User',
            'patronymic' => 'Example',
            'street' => 'Main',
            'nickname' => 'telegram_user',
            'phone' => '0671112233',
            'telegram_code' => '123456',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }
}
