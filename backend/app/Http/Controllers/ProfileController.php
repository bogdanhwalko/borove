<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ProfileChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
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

        // Filter to only the fields that actually differ from current
        $changes = [];
        foreach ($data as $key => $value) {
            if ($user->$key !== $value) {
                $changes[$key] = $value;
            }
        }

        if (empty($changes)) {
            return response()->json($this->userPayload($user));
        }

        // Admins skip moderation
        if ($user->is_admin) {
            $user->update($changes);
            ActivityLog::create([
                'user_id'     => $user->id,
                'action'      => 'profile_updated',
                'description' => 'Оновлено поля: ' . implode(', ', array_keys($changes)),
            ]);
            return response()->json($this->userPayload($user->fresh()));
        }

        // Replace existing pending request (one per user at a time)
        $existing = ProfileChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        $newPayload = array_merge(
            $existing && is_array($existing->payload) ? $existing->payload : [],
            $changes
        );

        if ($existing) {
            $existing->update(['payload' => $newPayload]);
        } else {
            ProfileChangeRequest::create([
                'user_id' => $user->id,
                'payload' => $newPayload,
                'status'  => 'pending',
            ]);
        }

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'profile_change_requested',
            'description' => 'Зміни на модерації: ' . implode(', ', array_keys($changes)),
        ]);

        return response()->json($this->userPayload($user->fresh()));
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        // Admins skip moderation
        if ($user->is_admin) {
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

            return response()->json($this->userPayload($user->fresh()));
        }

        // Store in pending bucket; will be moved/applied on approval
        $path = $request->file('avatar')->store('avatars-pending', 'public');

        $existing = ProfileChangeRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            // replace previous pending avatar
            if ($existing->avatar_path) {
                Storage::disk('public')->delete($existing->avatar_path);
            }
            $existing->update(['avatar_path' => $path]);
        } else {
            ProfileChangeRequest::create([
                'user_id'     => $user->id,
                'payload'     => [],
                'avatar_path' => $path,
                'status'      => 'pending',
            ]);
        }

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'avatar_change_requested',
            'description' => 'Нове фото профілю на модерації',
        ]);

        return response()->json($this->userPayload($user->fresh()));
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

    private function userPayload($user): array
    {
        $base = $user->only([
            'id', 'last_name', 'first_name', 'patronymic',
            'street', 'nickname', 'phone', 'is_admin', 'avatar_path',
        ]);

        // Attach pending change snapshot if any (so frontend can show indicator)
        if (!$user->is_admin) {
            $req = ProfileChangeRequest::where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();
            $base['pending_request'] = $req ? $this->requestPayload($req) : null;
        } else {
            $base['pending_request'] = null;
        }

        return $base;
    }

    private function requestPayload(ProfileChangeRequest $req): array
    {
        return [
            'id'          => $req->id,
            'payload'     => $req->payload ?? [],
            'avatar_path' => $req->avatar_path,
            'created_at'  => $req->created_at,
        ];
    }
}
