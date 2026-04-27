<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ride extends Model
{
    protected $fillable = [
        'from_place', 'to_place', 'ride_date', 'ride_time',
        'seats', 'name', 'contact', 'comment',
    ];

    protected $casts = [
        'ride_date' => 'date',
        'seats'     => 'integer',
    ];
}
