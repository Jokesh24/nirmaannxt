<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'ordering_for',
        'site_name',
        'site_photo',
        'company_name',
        'receiver_name',
        'gstin',
        'delivery_address',
        'delivery_landmark', 
        'delivery_pincode',  
        'delivery_gst',      
        'billing_address',
        'is_billing_same',
        'billing_landmark',  
        'billing_pincode',   
        'billing_gst',       
        'address_title',
        'latitude',
        'longitude',
        'status',
        'is_selected'
    ];

    // Automatically append full URL for the API response
    public function getSitePhotoAttribute($value)
    {
        return $value ? asset($value) : null;
    }

    public function getOrderingForLabelAttribute()
    {
        if ($this->ordering_for == 1) {
            return 'Individual';
        } elseif ($this->ordering_for == 2) {
            return 'Business';
        }
        return null;
    }

    // Relationship mapping back to the AppUser model
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}