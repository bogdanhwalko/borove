<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Services\ModerationNotifier;
use App\Services\UserRatingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $articles = Article::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get(['id', 'slug', 'title', 'category', 'status', 'views', 'published_at', 'created_at']);

        return response()->json($articles);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'    => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'summary'  => 'required|string|max:500',
            'body'     => 'required|string|min:200',
            'image'    => 'nullable|image|max:10240',
        ]);

        $user = $request->user();

        $authorName = trim(($user->last_name ?? '') . ' ' . ($user->first_name ?? ''));
        if ($authorName === '') {
            $authorName = $user->nickname ?: 'Користувач';
        }

        $slug = $this->uniqueSlug($data['title']);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('articles', 'public');
        }

        $status = app(UserRatingService::class)->statusForArticle($user);

        $article = Article::create([
            'slug'         => $slug,
            'category'     => $data['category'],
            'title'        => $data['title'],
            'summary'      => $data['summary'],
            'body'         => $data['body'],
            'author'       => $authorName,
            'image_seed'   => Str::slug($data['title']) ?: 'article',
            'image_path'   => $imagePath,
            'views'        => 0,
            'published_at' => now()->toDateString(),
            'user_id'      => $user->id,
            'status'       => $status,
        ]);

        if ($article->status === 'pending') {
            app(ModerationNotifier::class)->notifyPending(
                '📰 Стаття',
                $article->title,
                $user,
                url('/admin') . '#moderation-articles'
            );
        }

        return response()->json($article, 201);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'article';
        $slug = mb_substr($base, 0, 180);
        $i    = 0;
        while (Article::where('slug', $slug)->exists()) {
            $i++;
            $slug = mb_substr($base, 0, 180 - strlen('-' . $i)) . '-' . $i;
        }
        return $slug;
    }
}
