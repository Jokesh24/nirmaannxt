<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorFeedback extends Model
{
    use HasFactory;

    // Explicitly define table name since it is singular
    protected $table = 'vendor_feedback';

    // Mass-assignment protection
    protected $fillable = [
        'user_id',
        'vendor_id',
        'description',
    ];

    /**
     * Relationship: User
     */
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Relationship: Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}