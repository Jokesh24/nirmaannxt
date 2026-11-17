<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimatorMaterial extends Model
{
    use HasFactory;
    protected $table = 'estimator_materials';

    // Updated product_id to variant_id
    protected $fillable = ['project_id', 'category_id', 'variant_id', 'base_quantity', 'display_unit'];

    protected $casts = [
        'base_quantity' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    // New Variant Relationship
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}