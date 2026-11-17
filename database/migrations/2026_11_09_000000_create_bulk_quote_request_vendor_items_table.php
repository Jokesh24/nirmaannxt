<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-item price quotes submitted by a vendor for a bulk quote request.
     *
     * A single vendor quote (bulk_quote_request_vendors) can now hold one row
     * here per item the vendor actually offers for bulk, so the admin and the
     * customer always know exactly which items a vendor priced.
     */
    public function up(): void
    {
        // Safe to run even if the table was created manually via SQL.
        if (Schema::hasTable('bulk_quote_request_vendor_items')) {
            return;
        }

        Schema::create('bulk_quote_request_vendor_items', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('bulk_quote_request_vendor_id');
            $table->unsignedBigInteger('bulk_quote_request_item_id');
            $table->unsignedBigInteger('variant_id');

            $table->decimal('quoted_unit_price', 12, 2)->nullable();
            $table->decimal('quoted_amount', 14, 2)->nullable();

            $table->timestamps();

            $table->unique(
                ['bulk_quote_request_vendor_id', 'bulk_quote_request_item_id'],
                'bqrv_items_unique'
            );
            $table->index('bulk_quote_request_vendor_id', 'bqrv_items_vendor_quote_idx');
            $table->index('bulk_quote_request_item_id', 'bqrv_items_quote_item_idx');
            $table->index('variant_id', 'bqrv_items_variant_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_quote_request_vendor_items');
    }
};
