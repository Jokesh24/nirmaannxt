<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'variant_name',
        'sku',
        'price',
        'offer_price',
        'instant_price',
        'instant_offer_price',
        'royalty_percentage',
        'stock',
        'unit',
        'qty',
        'best_before',
        'image',
        'gallery',
        'status',
        'trash',
        'short_description',
        'description',
        'attributes',
        'features',
        'is_featured',
        'is_trending'
    ];

    protected $casts = [
        'gallery' => 'array',
        'attributes' => 'array',
        'features' => 'array',
        'price' => 'float',
        'offer_price' => 'float',
        'instant_price' => 'float',
        'instant_offer_price' => 'float',
        'royalty_percentage' => 'float',
        'qty' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function vendorBulkProducts()
    {
        return $this->hasMany(VendorBulkProduct::class, 'variant_id');
    }
}