<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryPartner extends Model
{
    use HasFactory;

    protected $table = 'delivery_partners';

    protected $fillable = [
        'driver_id',
        'driver_name',
        'mobile_number',
        'alternate_mobile',
        'alternate_relation',
        'profile_photo',
        'current_address',
        'languages_known',
        'dl_number',
        'dl_photo',
        'aadhaar_number',
        'aadhaar_photo',
        'vehicle_reg_number',
        'vehicle_type',
        'vehicle_capacity',
        'rc_book',
        'vehicle_insurance',
        'vehicle_fitness',
        'vehicle_image_front',
        'vehicle_image_back',
        'vehicle_image_side',
        'bank_name',
        'bank_account_number',
        'bank_ifsc_code',
        'upi_id',
        'status',
        'position',
        'trash',
        'created_by',
        'created_by_role',
        'updated_by',
        'updated_by_role'
    ];

    protected $casts = [
        'languages_known' => 'array',
    ];
}