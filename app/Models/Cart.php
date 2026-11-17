<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'cart_group_id',
        'user_id',
        'address_id',
        'coupon_id',
        'order_type', // <-- ADD THIS
        'variant_id',
        'qty'
    ];

    protected $casts = [
        'qty' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function coupon()
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }
}