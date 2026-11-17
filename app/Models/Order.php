<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    // Industry Standard: Cast financial columns to decimal/float automatically
    protected $casts = [
        'subtotal' => 'float',
        'tax_amount' => 'float',
        'delivery_charges' => 'float',
        'discount_amount' => 'float',
        'grand_total' => 'float',
        'initial_payment' => 'float',
        'pending_payment' => 'float',
        'is_coupon_applied' => 'boolean',
    ];

    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'engineer_id', // Add this
        'vendor_id',   // Add this
        'order_type',   // Add this
        'delivery_partner_id',
        'subtotal',
        'tax_amount',
        'delivery_charges',
        'is_coupon_applied',
        'coupon_id',
        'discount_amount',
        'grand_total',
        'initial_payment',
        'pending_payment',
        'payment_type',
        'delivery_otp', // Added here
        'payment_status',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function coupon()
    {
        return $this->belongsTo(DiscountCoupon::class, 'coupon_id');
    }

    public function engineer()
    {
        return $this->belongsTo(Engineer::class, 'engineer_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    // Add this relationship at the bottom of the class
    public function deliveryPartner()
    {
        return $this->belongsTo(DeliveryPartner::class, 'delivery_partner_id');
    }
}