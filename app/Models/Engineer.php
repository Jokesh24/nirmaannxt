<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Engineer extends Model
{
    use HasFactory;

    protected $table = 'engineers';

    protected $fillable = [
        'engineer_id',
        'full_name',
        'mobile_number',
        'email',
        'profile_photo',
        'highest_qualification',
        'qualification_proof',
        'assigned_territory',
        'latitude',  // Added
        'longitude', // Added
        'aadhaar_number',
        'aadhaar_upload',
        'pan_number',
        'pan_upload',
        'bank_account_name',
        'bank_account_number',
        'bank_ifsc_code',
        'cancelled_cheque',
        'description',
        'status',
        'position',
        'trash',
        'added_by'
    ];
}