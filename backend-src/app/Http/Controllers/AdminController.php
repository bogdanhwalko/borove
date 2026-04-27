<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Album;
use App\Models\Announcement;
use App\Models\Photo;
use App\Models\Ride;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    private function guard(Request $request): void
    {
        if (! $request->user()?->is_admin) {
            abort(403, 'Forbidden');
        }
    }

    // ── Articles ──────────────────────────────────────────────

    public function indexArticles(Request $request): JsonResponse
    {
        $this->guard($request);

        $perPage = min((int) $request->input('per_page', 10), 100);
        $paged = Article::orderBy('published_at', 'desc')->paginate($perPage);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
    }

    public function storeArticle(Request $request): JsonResponse
    {
        $this->guard($request);

        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'slug'         => 'nullable|string|max:120',
            'summary'      => 'required|string|max:500',
            'body'         => 'required|string',
            'author'       => 'required|string|max:100',
            'image_seed'   => 'nullable|string|max:100',
            'published_at' => 'nullable|date',
        ]);

        $slug = Str::slug($data['slug'] ?? $data['title']);
        $base = $slug;
        $n = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        $article = Article::create([
            'slug'         => $slug,
            'category'     => $data['category'],
            'title'        => $data['title'],
            'summary'      => $data['summary'],
            'body'         => $data['body'],
            'author'       => $data['author'],
            'image_seed'   => $data['image_seed'] ?? Str::random(8),
            'published_at' => $data['published_at'] ?? now()->toDateString(),
            'views'        => 0,
        ]);

        return response()->json($article, 201);
    }

    public function updateArticle(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        $article = Article::findOrFail($id);

        $data = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'category'     => 'sometimes|string|max:100',
            'summary'      => 'sometimes|string|max:500',
            'body'         => 'sometimes|string',
            'author'       => 'sometimes|string|max:100',
            'image_seed'   => 'sometimes|string|max:100',
            'published_at' => 'sometimes|date',
        ]);

        $article->update($data);

        return response()->json($article);
    }

    public function destroyArticle(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        Article::findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    // ── Announcements ─────────────────────────────────────────

    public function destroyAnnouncement(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        $ann = Announcement::findOrFail($id);

        if ($ann->image_path) {
            Storage::disk('public')->delete($ann->image_path);
        }

        $ann->delete();

        return response()->json(['ok' => true]);
    }

    // ── Rides ─────────────────────────────────────────────────

    public function destroyRide(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        Ride::findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    // ── Albums ────────────────────────────────────────────────

    public function indexPendingAlbums(Request $request): JsonResponse
    {
        $this->guard($request);

        $albums = Album::where('status', 'pending')
            ->with(['photos', 'user:id,nickname,first_name,last_name'])
            ->withCount('photos')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($albums);
    }

    public function publishAlbum(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        Album::findOrFail($id)->update(['status' => 'published']);

        return response()->json(['ok' => true]);
    }

    public function setCover(Request $request, int $albumId): JsonResponse
    {
        $this->guard($request);

        $request->validate(['photo_id' => 'required|integer']);

        $album = Album::findOrFail($albumId);
        $photo = Photo::where('id', $request->input('photo_id'))
            ->where('album_id', $albumId)
            ->firstOrFail();

        $album->update(['cover_path' => $photo->file_path]);

        return response()->json(['ok' => true]);
    }

    public function storeAlbum(Request $request): JsonResponse
    {
        $this->guard($request);

        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'cover_seed' => 'nullable|string|max:100',
            'album_date' => 'nullable|date',
        ]);

        $slug = Str::slug($data['title']);
        $base = $slug;
        $n = 1;
        while (Album::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        $album = Album::create([
            'slug'       => $slug,
            'title'      => $data['title'],
            'cover_seed' => $data['cover_seed'] ?? Str::random(8),
            'album_date' => $data['album_date'] ?? now()->toDateString(),
        ]);

        return response()->json($album->loadCount('photos'), 201);
    }

    public function destroyAlbum(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        $album = Album::with('photos')->findOrFail($id);

        foreach ($album->photos as $photo) {
            if ($photo->file_path) {
                Storage::disk('public')->delete($photo->file_path);
            }
        }

        $album->delete();

        return response()->json(['ok' => true]);
    }

    // ── Photos ────────────────────────────────────────────────

    public function storePhoto(Request $request, int $albumId): JsonResponse
    {
        $this->guard($request);

        $album = Album::findOrFail($albumId);

        $request->validate([
            'photo'   => 'required|image|max:20480',
            'caption' => 'nullable|string|max:200',
        ]);

        $path     = $request->file('photo')->store('photos', 'public');
        $maxOrder = $album->photos()->max('sort_order') ?? 0;

        $photo = Photo::create([
            'album_id'   => $album->id,
            'file_path'  => $path,
            'image_seed' => null,
            'caption'    => $request->input('caption'),
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json($photo, 201);
    }

    public function destroyPhoto(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        $photo = Photo::findOrFail($id);

        if ($photo->file_path) {
            Storage::disk('public')->delete($photo->file_path);
        }

        $photo->delete();

        return response()->json(['ok' => true]);
    }
}
