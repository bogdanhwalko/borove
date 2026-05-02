<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ride extends Model
{
    protected $fillable = [
        'from_place', 'to_place', 'ride_date', 'ride_time',
        'seats', 'name', 'contact', 'comment', 'user_id', 'status',
    ];

    protected $casts = [
        'ride_date' => 'date',
        'seats'     => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
