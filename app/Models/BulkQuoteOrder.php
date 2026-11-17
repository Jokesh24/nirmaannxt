<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A confirmed bulk order created from the vendor items the customer locked.
 *
 * One order per bulk quote request (enforced by a unique index on
 * bulk_quote_request_id).
 */
class BulkQuoteOrder extends Model
{
    use HasFactory;

    protected $table = 'bulk_quote_orders';

    protected $fillable = [
        'bulk_quote_request_id',
        'user_id',
        'order_number',
        'transaction_id',
        'sub_total',
        'gst_amount',
        'delivery_charges',
        'total_payable',
        'total_items',
        'total_quantity',
        'status',
        'confirmed_at',
    ];

    protected $casts = [
        'sub_total' => 'float',
        'gst_amount' => 'float',
        'delivery_charges' => 'float',
        'total_payable' => 'float',
        'total_items' => 'integer',
        'total_quantity' => 'float',
        'confirmed_at' => 'datetime',
    ];

    public function quote()
    {
        return $this->belongsTo(BulkQuoteRequest::class, 'bulk_quote_request_id');
    }

    public function user()
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
