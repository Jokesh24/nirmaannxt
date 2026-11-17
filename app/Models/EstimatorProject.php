<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimatorProject extends Model
{
    use HasFactory;
    protected $table = 'estimator_projects';
    protected $fillable = ['type', 'name', 'status', 'trash'];

    public function materials()
    {
        return $this->hasMany(EstimatorMaterial::class, 'project_id');
    }

    // Add this new relationship
    public function projectUnits()
    {
        return $this->hasMany(EstimatorProjectUnit::class, 'project_id');
    }
}