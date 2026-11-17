<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkCart extends Model
{
    protected $table = 'bulk_carts';

    protected $fillable = ['user_id', 'variant_id', 'bulk_qty', 'qty', 'order_qty'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}