<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    public function shop(): HasOne
    {
        return $this->hasOne(Shop::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->orderByDesc('created_at');
    }

    protected $fillable = [
        'last_name',
        'first_name',
        'patronymic',
        'street',
        'nickname',
        'phone',
        'password',
        'is_admin',
        'avatar_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_admin' => 'boolean',
        'password' => 'hashed',
    ];
}
