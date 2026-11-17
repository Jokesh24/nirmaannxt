<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimatorProjectUnit extends Model
{
    use HasFactory;
    protected $table = 'estimator_project_units';
    protected $fillable = ['project_id', 'unit_id', 'unit_value'];

    protected $casts = [
        'unit_value' => 'float',
    ];
}