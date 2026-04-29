<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->userFields($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name'  => 'sometimes|string|max:100',
            'patronymic' => 'sometimes|nullable|string|max:100',
            'street'     => 'sometimes|nullable|string|max:200',
            'phone'      => ['sometimes', 'string', 'regex:/^0[0-9]{9}$/', 'unique:users,phone,' . $user->id],
            'nickname'   => 'sometimes|string|max:50|unique:users,nickname,' . $user->id,
        ]);

        $changed = [];
        $labels  = [
            'first_name' => "ім'я",
            'last_name'  => 'прізвище',
            'patronymic' => 'по батькові',
            'street'     => 'вулицю',
            'phone'      => 'телефон',
            'nickname'   => 'нікнейм',
        ];

        foreach ($data as $key => $value) {
            if ($user->$key !== $value) {
                $changed[] = $labels[$key] ?? $key;
            }
        }

        $user->update($data);

        if (!empty($changed)) {
            ActivityLog::create([
                'user_id'     => $user->id,
                'action'      => 'profile_updated',
                'description' => 'Оновлено: ' . implode(', ', $changed),
            ]);
        }

        return response()->json($this->userFields($user->fresh()));
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $path]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'avatar_uploaded',
            'description' => 'Завантажено нове фото профілю',
        ]);

        return response()->json($this->userFields($user->fresh()));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Поточний пароль невірний.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'password_changed',
            'description' => 'Змінено пароль',
        ]);

        return response()->json(['ok' => true]);
    }

    public function logs(Request $request): JsonResponse
    {
        $logs = ActivityLog::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($logs);
    }

    private function userFields($user): array
    {
        return $user->only([
            'id', 'last_name', 'first_name', 'patronymic',
            'street', 'nickname', 'phone', 'is_admin', 'avatar_path',
        ]);
    }
}
