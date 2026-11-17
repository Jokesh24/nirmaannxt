<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveRate extends Model
{
    use HasFactory;

    protected $table = 'live_rates';

    protected $fillable = [
        'material_name',
        'price',
        'unit',
        'price_change',
        'status'
    ];

    protected $casts = [
        'price' => 'float',
        'price_change' => 'float',
        'status' => 'integer',
    ];
}