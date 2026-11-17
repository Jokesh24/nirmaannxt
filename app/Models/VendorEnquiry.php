<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorEnquiry extends Model
{
    protected $table = 'vendor_enquiries';
    protected $fillable = ['user_id', 'vendor_id', 'name', 'email', 'mobile', 'location', 'description', 'is_seen'];
}