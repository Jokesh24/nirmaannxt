<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A single item-level price quote submitted by a vendor for a bulk quote request.
 *
 * A vendor that only supplies sand will only have a row for the sand item; a
 * vendor that supplies both sand and cement gets one row per item.
 */
class BulkQuoteRequestVendorItem extends Model
{
    use HasFactory;

    protected $table = 'bulk_quote_request_vendor_items';

    protected $fillable = [
        'bulk_quote_request_vendor_id',
        'bulk_quote_request_item_id',
        'variant_id',
        'quoted_unit_price',
        'quoted_amount',
        'gst_percentage',
        'delivery_charges',
    ];

    protected $casts = [
        'quoted_unit_price' => 'float',
        'quoted_amount' => 'float',
        'gst_percentage' => 'float',
        'delivery_charges' => 'float',
    ];

    public function vendorQuote()
    {
        return $this->belongsTo(BulkQuoteRequestVendor::class, 'bulk_quote_request_vendor_id');
    }

    public function quoteItem()
    {
        return $this->belongsTo(BulkQuoteRequestItem::class, 'bulk_quote_request_item_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
