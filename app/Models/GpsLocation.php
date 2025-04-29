<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GpsLocation extends Model
{
    protected $fillable = [
        'latitude',
        'longitude',
        'emergency',
        'device_id'
    ];

    protected $casts = [
        'emergency' => 'boolean'
    ];
}
