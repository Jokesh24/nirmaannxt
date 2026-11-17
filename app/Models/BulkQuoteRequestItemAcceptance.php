<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A locked acceptance of a vendor's per-item price quote.
 *
 * The unique index on bulk_quote_request_item_id guarantees that one bulk item
 * can only ever be accepted from a single vendor. The unit price and line total
 * are snapshotted here, so the accepted price stays locked even if the vendor
 * quote is edited later.
 */
class BulkQuoteRequestItemAcceptance extends Model
{
    use HasFactory;

    protected $table = 'bulk_quote_request_item_acceptances';

    protected $fillable = [
        'bulk_quote_request_id',
        'bulk_quote_request_item_id',
        'bulk_quote_request_vendor_id',
        'vendor_id',
        'quoted_unit_price',
        'quoted_amount',
        'status',
        'accepted_at',
    ];

    protected $casts = [
        'quoted_unit_price' => 'float',
        'quoted_amount' => 'float',
        'accepted_at' => 'datetime',
    ];

    public function quote()
    {
        return $this->belongsTo(BulkQuoteRequest::class, 'bulk_quote_request_id');
    }

    public function item()
    {
        return $this->belongsTo(BulkQuoteRequestItem::class, 'bulk_quote_request_item_id');
    }

    public function vendorQuote()
    {
        return $this->belongsTo(BulkQuoteRequestVendor::class, 'bulk_quote_request_vendor_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
}
