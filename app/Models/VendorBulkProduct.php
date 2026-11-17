<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorBulkProduct extends Model
{
    use HasFactory;

    protected $table = 'vendor_bulk_products';

    protected $fillable = [
        'vendor_id',
        'variant_id',
        'status',
        'trash',
        'created_by',
        'updated_by',
        'createdby_role',
        'updatedby_role'
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}