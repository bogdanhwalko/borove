<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = [
        'slug', 'category', 'title', 'summary', 'body',
        'author', 'image_seed', 'image_path', 'views', 'published_at',
        'user_id', 'status',
    ];

    protected $casts = [
        'published_at' => 'date',
        'views'        => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
