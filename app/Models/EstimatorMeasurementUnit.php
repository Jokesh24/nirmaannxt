<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimatorMeasurementUnit extends Model
{
    use HasFactory;
    protected $table = 'estimator_measurement_units';
    protected $fillable = ['type', 'unit_name', 'status'];
}