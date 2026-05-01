<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\Album;
use App\Models\Announcement;
use App\Models\FeedbackMessage;
use App\Models\Photo;
use App\Models\Product;
use App\Models\ProfileChangeRequest;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Validation\ValidationException;
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
            'image'        => 'nullable|image|max:10240',
            'published_at' => 'nullable|date',
        ]);

        $slug = Str::slug($data['slug'] ?? $data['title']);
        $base = $slug;
        $n = 1;
        while (Article::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $n++;
        }

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('articles', 'public');
        }

        $article = Article::create([
            'slug'         => $slug,
            'category'     => $data['category'],
            'title'        => $data['title'],
            'summary'      => $data['summary'],
            'body'         => $data['body'],
            'author'       => $data['author'],
            'image_seed'   => $data['image_seed'] ?? Str::random(8),
            'image_path'   => $imagePath,
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
            'image_seed'   => 'sometimes|nullable|string|max:100',
            'image'        => 'sometimes|image|max:10240',
            'remove_image' => 'sometimes|boolean',
            'published_at' => 'sometimes|date',
        ]);

        if ($request->boolean('remove_image') && $article->image_path) {
            Storage::disk('public')->delete($article->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($article->image_path) {
                Storage::disk('public')->delete($article->image_path);
            }
            $data['image_path'] = $request->file('image')->store('articles', 'public');
        }

        unset($data['image'], $data['remove_image']);

        $article->update($data);

        return response()->json($article);
    }

    public function destroyArticle(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        $article = Article::findOrFail($id);
        if ($article->image_path) {
            Storage::disk('public')->delete($article->image_path);
        }
        $article->delete();

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

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paged = Album::where('status', 'pending')
            ->with(['photos', 'user:id,nickname,first_name,last_name'])
            ->withCount('photos')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
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

    // ── Profile change moderation ──────────────────────────────

    public function indexProfileRequests(Request $request): JsonResponse
    {
        $this->guard($request);

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paged = ProfileChangeRequest::where('status', 'pending')
            ->with('user:id,last_name,first_name,patronymic,street,nickname,phone,avatar_path')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
    }

    public function approveProfileRequest(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        $req = ProfileChangeRequest::with('user')->findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json(['message' => 'Запит уже оброблено'], 422);
        }

        $user    = $req->user;
        $payload = is_array($req->payload) ? $req->payload : [];
        $changes = [];

        // Re-validate uniqueness (someone else might have taken the nickname/phone since submission)
        if (!empty($payload['nickname'])) {
            $taken = User::where('nickname', $payload['nickname'])->where('id', '!=', $user->id)->exists();
            if ($taken) {
                throw ValidationException::withMessages(['nickname' => ['Нікнейм уже зайнятий — попросіть користувача обрати інший']]);
            }
        }
        if (!empty($payload['phone'])) {
            $taken = User::where('phone', $payload['phone'])->where('id', '!=', $user->id)->exists();
            if ($taken) {
                throw ValidationException::withMessages(['phone' => ['Телефон уже зайнятий']]);
            }
        }

        foreach ($payload as $k => $v) {
            $changes[$k] = $v;
        }

        if ($req->avatar_path) {
            // Move from avatars-pending to avatars (by re-using the same path is fine since both are 'public')
            if ($user->avatar_path && $user->avatar_path !== $req->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $changes['avatar_path'] = $req->avatar_path;
        }

        if (!empty($changes)) {
            $user->update($changes);
        }

        $req->update([
            'status'         => 'approved',
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at'    => now(),
        ]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'action'      => 'profile_change_approved',
            'description' => 'Зміни профілю схвалено адміністратором',
        ]);

        return response()->json(['ok' => true]);
    }

    public function rejectProfileRequest(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        $req = ProfileChangeRequest::findOrFail($id);
        if ($req->status !== 'pending') {
            return response()->json(['message' => 'Запит уже оброблено'], 422);
        }

        // Discard pending avatar file (was uploaded into avatars-pending bucket)
        if ($req->avatar_path) {
            Storage::disk('public')->delete($req->avatar_path);
        }

        $req->update([
            'status'         => 'rejected',
            'reviewed_by_id' => $request->user()->id,
            'reviewed_at'    => now(),
            'avatar_path'    => null,
        ]);

        ActivityLog::create([
            'user_id'     => $req->user_id,
            'action'      => 'profile_change_rejected',
            'description' => 'Зміни профілю відхилено адміністратором',
        ]);

        return response()->json(['ok' => true]);
    }

    public function indexFeedbackMessages(Request $request): JsonResponse
    {
        $this->guard($request);

        $perPage = min((int) $request->input('per_page', 20), 100);
        $status = $request->input('status', 'new');

        $query = FeedbackMessage::with('user:id,last_name,first_name,nickname,phone')
            ->orderByRaw("status = 'new' desc")
            ->orderBy('created_at', 'desc');

        if (in_array($status, ['new', 'closed'], true)) {
            $query->where('status', $status);
        }

        $paged = $query->paginate($perPage);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
    }

    public function closeFeedbackMessage(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        $message = FeedbackMessage::findOrFail($id);
        $message->update([
            'status'       => 'closed',
            'closed_by_id' => $request->user()->id,
            'closed_at'    => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function destroyFeedbackMessage(Request $request, int $id): JsonResponse
    {
        $this->guard($request);

        FeedbackMessage::findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    // ── Moderation: announcements / rides / products / articles ─────

    public function indexPendingAnnouncements(Request $request): JsonResponse
    {
        $this->guard($request);
        return response()->json(
            Announcement::where('status', 'pending')
                ->with('user:id,nickname,first_name,last_name,phone')
                ->latest()->get()
        );
    }

    public function publishAnnouncement(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        Announcement::findOrFail($id)->update(['status' => 'published']);
        return response()->json(['ok' => true]);
    }

    public function rejectAnnouncement(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        $a = Announcement::findOrFail($id);
        if ($a->image_path) Storage::disk('public')->delete($a->image_path);
        $a->delete();
        return response()->json(['ok' => true]);
    }

    public function indexPendingRides(Request $request): JsonResponse
    {
        $this->guard($request);
        return response()->json(
            Ride::where('status', 'pending')
                ->with('user:id,nickname,first_name,last_name,phone')
                ->latest()->get()
        );
    }

    public function publishRide(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        Ride::findOrFail($id)->update(['status' => 'published']);
        return response()->json(['ok' => true]);
    }

    public function rejectRide(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        Ride::findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }

    public function indexPendingProducts(Request $request): JsonResponse
    {
        $this->guard($request);
        return response()->json(
            Product::where('status', 'pending')
                ->with(['shop:id,user_id,name', 'shop.user:id,nickname,first_name,last_name,phone'])
                ->latest()->get()
        );
    }

    public function publishProduct(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        Product::findOrFail($id)->update(['status' => 'published']);
        return response()->json(['ok' => true]);
    }

    public function rejectProduct(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        $p = Product::findOrFail($id);
        if ($p->photo_path) Storage::disk('public')->delete($p->photo_path);
        $p->delete();
        return response()->json(['ok' => true]);
    }

    public function indexPendingArticles(Request $request): JsonResponse
    {
        $this->guard($request);
        return response()->json(
            Article::where('status', 'pending')
                ->with('user:id,nickname,first_name,last_name,phone')
                ->latest()->get()
        );
    }

    public function publishArticle(Request $request, int $id): JsonResponse
    {
        $this->guard($request);
        Article::findOrFail($id)->update(['status' => 'published']);
        return response()->json(['ok' => true]);
    }
}
