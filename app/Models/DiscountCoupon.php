<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCoupon extends Model
{
    use HasFactory;

    protected $table = 'discount_coupons';

    protected $fillable = [
        'logo',
        'title',
        'code',
        'coupon_type',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'threshold_amount',
        'max_discount',
        'usage_limit',
        'start_date',
        'end_date',
        'description',
        'status',
        'position',
        'trash',
        'added_by',
    ];
}