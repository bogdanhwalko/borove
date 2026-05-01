<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramPhoneVerification extends Model
{
    protected $fillable = [
        'phone',
        'telegram_user_id',
        'telegram_chat_id',
        'code_hash',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];
}
