<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkQuoteRequestItem extends Model
{
    protected $table = 'bulk_quote_request_items';

    // Add 'order_qty' to the fillable array
    protected $fillable = ['bulk_quote_request_id', 'variant_id', 'qty', 'order_qty'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Per-vendor price quotes for this item.
     */
    public function vendorQuotes()
    {
        return $this->hasMany(\App\Models\BulkQuoteRequestVendorItem::class, 'bulk_quote_request_item_id');
    }

    /**
     * The locked vendor acceptance for this item (at most one per item).
     */
    public function acceptance()
    {
        return $this->hasOne(\App\Models\BulkQuoteRequestItemAcceptance::class, 'bulk_quote_request_item_id');
    }
}