<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = optional($request->user('sanctum'))->id;

        $perPage = min((int) $request->input('per_page', 9), 100);
        $paged = Article::orderBy('published_at', 'desc')
            ->where(function ($q) use ($userId) {
                $q->where('status', 'published');
                if ($userId) $q->orWhere('user_id', $userId);
            })
            ->paginate($perPage, ['id', 'slug', 'category', 'title', 'summary', 'author', 'image_seed', 'image_path', 'views', 'published_at', 'status', 'user_id']);

        return response()->json([
            'data'         => $paged->items(),
            'current_page' => $paged->currentPage(),
            'last_page'    => $paged->lastPage(),
            'total'        => $paged->total(),
            'per_page'     => $paged->perPage(),
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $article = Article::where('slug', $slug)->firstOrFail();

        $userId = optional($request->user('sanctum'))->id;
        if ($article->status !== 'published'
            && (!$userId || (int) $article->user_id !== (int) $userId)) {
            abort(404);
        }

        $article->increment('views');

        return response()->json($article);
    }
}
