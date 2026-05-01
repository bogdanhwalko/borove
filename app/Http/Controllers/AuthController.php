<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramPhoneVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request, TelegramPhoneVerifier $verifier): JsonResponse
    {
        $data = $request->validate([
            'last_name'             => 'required|string|max:100',
            'first_name'            => 'required|string|max:100',
            'patronymic'            => 'required|string|max:100',
            'street'                => 'required|string|max:200',
            'nickname'              => 'required|string|max:50|unique:users',
            'phone'                 => ['required', 'string', 'regex:/^0[0-9]{9}$/', 'unique:users'],
            'telegram_code'         => ['required', 'string', 'regex:/^[0-9]{6}$/'],
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($data, $verifier): User {
            if (!$verifier->verify($data['phone'], $data['telegram_code'])) {
                throw ValidationException::withMessages([
                    'telegram_code' => ['Невірний або прострочений код Telegram. Отримайте новий код у боті.'],
                ]);
            }

            return User::create([
                'last_name'  => $data['last_name'],
                'first_name' => $data['first_name'],
                'patronymic' => $data['patronymic'],
                'street'     => $data['street'],
                'nickname'   => $data['nickname'],
                'phone'      => $data['phone'],
                'password'   => Hash::make($data['password']),
            ]);
        });

        $token = $user->createToken('borove-app')->plainTextToken;

        return response()->json([
            'user'  => $this->userPayload($user),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        // Normalize phone: strip non-digits, remove country code 38
        $phone = preg_replace('/\D/', '', $request->phone);
        if (strlen($phone) === 12 && str_starts_with($phone, '38')) {
            $phone = substr($phone, 2);
        }

        $user = User::where('phone', $phone)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['Невірний номер телефону або пароль.'],
            ]);
        }

        $token = $user->createToken('borove-app')->plainTextToken;

        return response()->json([
            'user'  => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Вийшли успішно.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    private function userPayload(User $user): array
    {
        $payload = $user->only([
            'id', 'last_name', 'first_name', 'patronymic',
            'street', 'nickname', 'phone', 'is_admin', 'avatar_path',
        ]);
        if ($payload['avatar_path'] && !Storage::disk('public')->exists($payload['avatar_path'])) {
            $payload['avatar_path'] = null;
        }
        $payload['avatar_version'] = optional($user->updated_at)->timestamp;

        return $payload;
    }
}
