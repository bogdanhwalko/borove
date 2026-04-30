<?php

namespace App\Http\Controllers;

use App\Models\FeedbackMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => 'required|string|min:10|max:2000',
        ]);

        $user = $request->user();
        $fullName = trim(implode(' ', array_filter([
            $user->last_name,
            $user->first_name,
            $user->patronymic,
        ])));

        $message = FeedbackMessage::create([
            'user_id' => $user->id,
            'name'    => mb_substr($fullName ?: ($user->nickname ?: 'Користувач #' . $user->id), 0, 120),
            'contact' => mb_substr($this->formatUaPhone($user->phone), 0, 120),
            'message' => $data['message'],
        ]);

        return response()->json([
            'ok' => true,
            'id' => $message->id,
        ], 201);
    }

    private function formatUaPhone(?string $raw): string
    {
        $digits = preg_replace('/\D/', '', (string) $raw);
        if (strlen($digits) === 12 && str_starts_with($digits, '380')) {
            $digits = '0' . substr($digits, 3);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return '+380 ' . substr($digits, 1, 2) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 2) . ' ' . substr($digits, 8);
        }

        return $raw ? (string) $raw : 'Телефон не вказано';
    }
}
