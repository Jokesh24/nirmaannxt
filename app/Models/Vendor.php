<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'vendors';

    protected $fillable = [
        'business_name',
        'contact_person',
        'business_mobile',
        'business_email',
        'business_type_id', // Added
        'category_id',
        'gst_number',
        'pan_number', // Added
        'gst_certificate', // Added
        'status',
        'position',
        'trash',
        'state_id',
        'city_id',
        'company_logo',
        'address',
        'latitude', // Added
        'longitude', // Added
        'delivery_radius_km', // Added
        'description',
        'products_supplied', // Added
        'daily_capacity', // Added
        'weighbridge_available', // Added
        'bank_account_name', // Added
        'bank_account_number', // Added
        'bank_ifsc_code', // Added
        'cancelled_cheque', // Added
        'added_by'
    ];

    protected $casts = [
        'products_supplied' => 'array',
        'weighbridge_available' => 'boolean'
    ];

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    public function enquiries()
    {
        return $this->hasMany(VendorEnquiry::class, 'vendor_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(VendorFeedback::class, 'vendor_id');
    }

    public function bulkProducts()
    {
        return $this->hasMany(VendorBulkProduct::class, 'vendor_id');
    }
}