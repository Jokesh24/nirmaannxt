<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorStock extends Model
{
    use HasFactory;

    protected $table = 'vendor_stocks';

    protected $fillable = [
        'vendor_id',
        'product_variant_id',
        'product_id',
        'batch_number',
        'invoice_number',
        'received_date',
        'manufacturing_date',
        'expiry_date',
        'quantity',
        'purchase_price',
        'remarks',
        'added_by',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}