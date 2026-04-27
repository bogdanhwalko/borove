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
            'type'    => 'required|in:urgent,event,info,services',
            'title'   => 'required|string|max:255',
            'body'    => 'required|string',
            'contact' => 'nullable|string|max:255',
            'image'   => 'nullable|image|max:10240',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $ann = Announcement::create([
            'type'       => $request->input('type'),
            'title'      => $request->input('title'),
            'body'       => $request->input('body'),
            'contact'    => $request->input('contact'),
            'image_path' => $imagePath,
        ]);

        return response()->json($ann, 201);
    }
}
