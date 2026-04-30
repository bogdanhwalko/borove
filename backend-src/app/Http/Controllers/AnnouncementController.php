<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::latest();

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
        $phone = $user->phone ? '+380' . substr($user->phone, 1) : '';

        if (!$phone) {
            return response()->json(['message' => 'У вашому профілі не вказано телефон. Заповніть профіль перед створенням оголошення.'], 422);
        }

        $contact = $nameDisplay . ', ' . $phone;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $ann = Announcement::create([
            'type'       => $request->input('type'),
            'title'      => $request->input('title'),
            'body'       => $request->input('body'),
            'contact'    => $contact,
            'image_path' => $imagePath,
        ]);

        return response()->json($ann, 201);
    }
}
