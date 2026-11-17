<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReason extends Model
{
    use HasFactory;

    protected $table = 'product_reasons';

    protected $fillable = [
        'product_id',
        'icon',
        'title',
        'tagline'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}