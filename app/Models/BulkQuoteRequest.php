<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkQuoteRequest extends Model
{
    protected $table = 'bulk_quote_requests';
    protected $fillable = [
        'user_id',
        'delivery_location',
        'latitude',
        'longitude',
        'preferred_delivery_date',
        'payment_preference',
        'full_name',
        'phone_number',
        'gstin',
        'status',
        'is_notify'
    ];

    protected $casts = [
        'is_notify' => 'boolean',
    ];

    /**
     * Get the user who requested the bulk quote.
     */
    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }

    /**
     * Get the vendor assigned to this quote.
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function items()
    {
        return $this->hasMany(BulkQuoteRequestItem::class, 'bulk_quote_request_id');
    }

    public function assignedVendors()
    {
        return $this->hasMany(\App\Models\BulkQuoteRequestVendor::class, 'bulk_quote_request_id');
    }

    /**
     * The confirmed bulk order for this quote (at most one).
     */
    public function order()
    {
        return $this->hasOne(\App\Models\BulkQuoteOrder::class, 'bulk_quote_request_id');
    }

    /**
     * Distinct product-variant IDs represented by this quote's items.
     *
     * A vendor is eligible to receive this quote when it has at least one active
     * vendor_bulk_products row for one of these variant IDs. This is the actual
     * source of truth for "what a vendor gives for bulk", unlike the coarse
     * vendors.products_supplied category list.
     *
     * Requires `items` to be eager loaded for the quote.
     *
     * @return array<int>
     */
    public function variantIds(): array
    {
        return $this->items
            ->pluck('variant_id')
            ->filter()
            // Cast to int so json_encode() yields a clean `[1,2,3]` array with no
            // embedded quotes (safe to inline inside an HTML onclick attribute).
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}