<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['type', 'title', 'body', 'contact', 'image_path'];
}
