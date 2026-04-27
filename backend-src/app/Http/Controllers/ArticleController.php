<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 9), 100);
        $paged = Article::orderBy('published_at', 'desc')
            ->paginate($perPage, ['id', 'slug', 'category', 'title', 'summary', 'author', 'image_seed', 'views', 'published_at']);

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
        $article = Article::where('slug', $slug)->firstOrFail();
        $article->increment('views');

        return response()->json($article);
    }
}
