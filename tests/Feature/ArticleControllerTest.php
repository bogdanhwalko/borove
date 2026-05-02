<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_index_includes_uploaded_image_path(): void
    {
        Article::create([
            'slug' => 'uploaded-photo-article',
            'category' => 'News',
            'title' => 'Article with uploaded photo',
            'summary' => 'Short summary',
            'body' => 'Full body',
            'author' => 'Admin',
            'image_seed' => 'fallback-seed',
            'image_path' => 'articles/uploaded.jpg',
            'published_at' => now()->toDateString(),
        ]);

        $this->getJson('/api/articles?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.image_path', 'articles/uploaded.jpg');
    }
}
