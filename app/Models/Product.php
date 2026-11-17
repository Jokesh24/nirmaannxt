<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    protected $fillable = [
        'brand_id',
        'category_id',
        'subcategory_id',
        'name',
        'sku',
        'price',
        'offer_price',
        'instant_price',
        'instant_offer_price',
        'royalty_percentage',
        'stock',
        'image',
        'gallery',
        'status',
        'short_description',
        'description',
        'is_featured',
        'is_trending',
        'inventory_note',
        'trash',
        'unit',
        'qty',
        'sla_days',
        'best_before',
    ];

    protected $casts = [
        'gallery' => 'array',
        'price' => 'float',
        'offer_price' => 'float',
        'instant_price' => 'float',
        'instant_offer_price' => 'float',
        'royalty_percentage' => 'float',
        'qty' => 'float',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(ProductSubcategory::class, 'subcategory_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    // Add this beneath public function variants()
    public function reasons()
    {
        return $this->hasMany(ProductReason::class, 'product_id');
    }
}