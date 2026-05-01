<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Services\ModerationNotifier;
use App\Services\UserRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::latest();

        $userId = optional($request->user())->id;
        $query->where(function ($q) use ($userId) {
            $q->where('status', 'published');
            if ($userId) $q->orWhere('user_id', $userId);
        });

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        $perPage = min((int) $request->input('per_page', 6), 50);
        $paged   = $query->paginate($perPage);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'type'  => 'required|in:urgent,event,info,services',
            'title' => 'required|string|max:255',
            'body'  => 'required|string',
            'image' => 'nullable|image|max:10240',
        ]);

        $user = $request->user();

        // Derive contact from authenticated user's profile (not user-editable)
        // Format: "Прізвище Ім'я (нікнейм), +380XXXXXXXXX"
        $fullName = trim(($user->last_name ?? '') . ' ' . ($user->first_name ?? ''));
        if ($fullName !== '') {
            $nameDisplay = $user->nickname ? $fullName . ' (' . $user->nickname . ')' : $fullName;
        } else {
            $nameDisplay = $user->nickname ?: 'Користувач';
        }
        $phone = $this->formatUaPhone($user->phone);

        if (!$phone) {
            return response()->json(['message' => 'У вашому профілі не вказано телефон. Заповніть профіль перед створенням оголошення.'], 422);
        }

        $contact = $nameDisplay . ', ' . $phone;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $status = app(UserRatingService::class)->statusForAnnouncement($user);

        $ann = Announcement::create([
            'type'       => $request->input('type'),
            'title'      => $request->input('title'),
            'body'       => $request->input('body'),
            'contact'    => $contact,
            'image_path' => $imagePath,
            'user_id'    => $user->id,
            'status'     => $status,
        ]);

        if ($status === 'pending') {
            app(ModerationNotifier::class)->notifyPending(
                '📋 Оголошення',
                $ann->title,
                $user,
                url('/admin') . '#moderation-announcements'
            );
        }

        return response()->json($ann, 201);
    }

    private function formatUaPhone(?string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw ?? '');

        if (strlen($digits) === 12 && str_starts_with($digits, '380')) {
            $digits = '0' . substr($digits, 3);
        }

        if (strlen($digits) === 9) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            return sprintf(
                '+380 %s %s %s %s',
                substr($digits, 1, 2),
                substr($digits, 3, 3),
                substr($digits, 6, 2),
                substr($digits, 8, 2)
            );
        }

        return $raw ?? '';
    }
}
