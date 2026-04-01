<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'icon',
        'room',
        'is_on',
        'brightness',
        'speed',
        'temperature_setting',
        'color',
    ];

    protected $casts = [
        'is_on' => 'boolean',
        'brightness' => 'integer',
        'speed' => 'integer',
        'temperature_setting' => 'float',
    ];
}
