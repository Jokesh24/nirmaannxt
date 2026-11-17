<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks which vendors a given BulkQuoteRequest has been sent to, and each
 * vendor's response (status, quoted amount, notes).
 */
class BulkQuoteRequestVendor extends Model
{
    protected $table = 'bulk_quote_request_vendors';

    protected $fillable = [
        'bulk_quote_request_id',
        'vendor_id',
        'status',
        'quoted_amount',
        'vendor_notes',
        'sent_at',
        'responded_at',
        'expected_delivery_date',
        'gst_percentage',
        'delivery_charges',
    ];

    protected $casts = [
        'quoted_amount' => 'float',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
        'expected_delivery_date' => 'date',
        'gst_percentage' => 'float',
        'delivery_charges' => 'float',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function quoteRequest()
    {
        return $this->belongsTo(BulkQuoteRequest::class, 'bulk_quote_request_id');
    }

    /**
     * Item-level price quotes submitted by this vendor.
     */
    public function itemQuotes()
    {
        return $this->hasMany(\App\Models\BulkQuoteRequestVendorItem::class, 'bulk_quote_request_vendor_id');
    }
}