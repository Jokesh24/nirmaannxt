<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'display_order',
        'image',
        'status',
        'description',
        'trash'
    ];

    public function subcategories()
    {
        return $this->hasMany(ProductSubcategory::class, 'category_id')->where('trash', 0);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}