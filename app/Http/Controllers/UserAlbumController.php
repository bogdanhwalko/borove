<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use App\Services\ModerationNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserAlbumController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'album_date'  => 'nullable|date',
            'description' => 'nullable|string|max:500',
            'photos'      => 'required|array|min:1|max:30',
            'photos.*'    => 'image|max:20480',
        ]);

        $slug = Str::slug($request->input('title')) ?: 'album';
        $base = $slug;
        $n = 1;
        while (Album::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        $album = Album::create([
            'slug'        => $slug,
            'title'       => $request->input('title'),
            'cover_seed'  => Str::random(8),
            'album_date'  => $request->input('album_date') ?? now()->toDateString(),
            'description' => $request->input('description'),
            'status'      => 'pending',
            'user_id'     => $request->user()->id,
        ]);

        $coverIndex = (int) $request->input('cover_index', 0);
        $coverPath  = null;

        foreach ($request->file('photos') as $i => $file) {
            $path = $file->store('photos', 'public');
            if ($i === $coverIndex) {
                $coverPath = $path;
            }
            Photo::create([
                'album_id'   => $album->id,
                'file_path'  => $path,
                'image_seed' => null,
                'sort_order' => $i,
            ]);
        }

        $album->update(['cover_path' => $coverPath]);

        app(ModerationNotifier::class)->notifyPending(
            '📷 Альбом',
            $album->title . ' · ' . count($request->file('photos')) . ' фото',
            $request->user(),
            url('/admin') . '#moderation-albums'
        );

        return response()->json(['ok' => true], 201);
    }
}
