<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'slug', 'category', 'title', 'summary', 'body',
        'author', 'image_seed', 'views', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'date',
        'views'        => 'integer',
    ];
}
