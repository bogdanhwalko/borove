<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $paged = Album::where('status', 'published')
            ->orderBy('album_date', 'desc')
            ->withCount('photos')
            ->paginate($perPage);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $album = Album::where('slug', $slug)
            ->where('status', 'published')
            ->with('photos')
            ->firstOrFail();

        return response()->json($album);
    }
}
