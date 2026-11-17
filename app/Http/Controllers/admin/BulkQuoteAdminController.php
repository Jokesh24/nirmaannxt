<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BulkQuoteRequest;
use App\Models\BulkQuoteRequestVendor;
use App\Models\BulkQuoteRequestVendorItem;
use App\Models\Vendor;
use App\Models\VendorBulkProduct;
use App\Traits\PushNotificationTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;


class BulkQuoteAdminController extends Controller
{
    use PushNotificationTrait;

    protected function statusMap(): array
    {
        return [
            'Pending' => 'Pending',
            'Sent To Vendors' => 'Sent To Vendors',
            'Quoted' => 'Quoted',
            'Quote Locked' => 'Quote Locked',
            'Confirmed' => 'Confirmed',
            'Completed' => 'Completed',
            'Cancelled' => 'Cancelled',
        ];
    }

    public function manage(Request $request)
    {
        $query = BulkQuoteRequest::with(['user', 'assignedVendors', 'items']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                    ->orWhere('full_name', 'like', "%$search%")
                    ->orWhere('phone_number', 'like', "%$search%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%$search%")
                            ->orWhere('mobile', 'like', "%$search%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('created_at', [
                $request->from_date . ' 00:00:00',
                $request->to_date . ' 23:59:59'
            ]);
        } elseif ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date . ' 00:00:00');
        } elseif ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
        }

        $quotes = $query->orderBy('id', 'desc')->paginate(10);
        $eligibleVendorsByQuote = $this->eligibleVendorIdsByQuote($quotes->getCollection());
        $vendors = Vendor::where('status', 1)->where('trash', 0)->get();
        $statusMap = $this->statusMap();

        return view('admin.bulk_quotes.manage_bulk_quotes', compact('quotes', 'vendors', 'statusMap', 'eligibleVendorsByQuote'));
    }

    public function view($id)
    {
        $quote = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'user',
            'assignedVendors.vendor'
        ])->findOrFail($id);

        $vendors = Vendor::where('status', 1)->where('trash', 0)->get();
        $assignedVendorIds = $quote->assignedVendors->pluck('vendor_id')->toArray();
        $eligibleVendorIds = $this->eligibleVendorIdsByQuote(collect([$quote]))[$quote->id] ?? [];
        $statusMap = $this->statusMap();

        return view('admin.bulk_quotes.view_bulk_quote', compact('quote', 'vendors', 'assignedVendorIds', 'eligibleVendorIds', 'statusMap'));
    }

    public function assignVendors(Request $request)
    {
        $request->validate([
            'bulk_quote_request_id' => 'required|exists:bulk_quote_requests,id',
            'vendor_ids' => 'required|array|min:1',
            'vendor_ids.*' => 'required|integer|exists:vendors,id',
        ], [
            'vendor_ids.required' => 'Please select at least one vendor.',
        ]);

        try {
            DB::beginTransaction();
            // $quote = BulkQuoteRequest::findOrFail($request->bulk_quote_request_id);
            $quote = BulkQuoteRequest::with('user')->findOrFail($request->bulk_quote_request_id);

            foreach ($request->vendor_ids as $vendorId) {
                BulkQuoteRequestVendor::updateOrCreate(
                    [
                        'bulk_quote_request_id' => $quote->id,
                        'vendor_id' => $vendorId,
                    ],
                    [
                        'status' => 'Pending',
                        'sent_at' => Carbon::now(),
                    ]
                );
            }

            if ($quote->status === 'Pending') {
                $quote->status = 'Sent To Vendors';
                $quote->save();
            }

            DB::commit();

            $count = count($request->vendor_ids);
            $label = $count === 1 ? 'vendor' : 'vendors';
            $displayOrderId = '#ORD' . str_pad($quote->id, 5, '0', STR_PAD_LEFT);

            // 1. Vendor Assignment Confirmation Notification
            $this->notifyCustomerForQuote(
                $quote,
                'Vendors Assigned to Your Quote',
                "NirmaanNXT has assigned {$count} qualified {$label} to your bulk quote request {$displayOrderId}.",
                'bulk_quote_sent'
            );

            // 2. Next-Step Notification
            $this->notifyCustomerForQuote(
                $quote,
                'Awaiting Vendor Quotations',
                "Your request {$displayOrderId} is now with the assigned {$label}. We will notify you as soon as their competitive quotes are submitted for your review.",
                'bulk_quote_sent'
            );

            return redirect()->back()->with('success', "Quote request sent to {$count} {$label} successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to assign vendors: ' . $e->getMessage());
        }
    }

    public function vendorView($quoteId, $vendorId)
    {
        $quote = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'items.variant.product.category',
            'user',
        ])->findOrFail($quoteId);

        $assignment = BulkQuoteRequestVendor::with('vendor')
            ->where('bulk_quote_request_id', $quoteId)
            ->where('vendor_id', $vendorId)
            ->firstOrFail();

        $suppliedVariantIds = $this->vendorSuppliedVariantIds($quote, $vendorId);

        $suppliedVariants = $quote->items
            ->filter(fn($item) => in_array((int) $item->variant_id, $suppliedVariantIds, true))
            ->groupBy('variant_id')
            ->map(function ($items, $variantId) {
                $first = $items->first();
                $variant = $first ? $first->variant : null;

                return [
                    'variantId' => (int) $variantId,
                    'variant' => $variant,
                    'product' => $variant ? $variant->product : null,
                    'totalQty' => (int) $items->sum('qty'),
                    'unit' => $variant ? $variant->unit : 'Units',
                    'items' => $items,
                ];
            })
            ->values();

        $variantQuotes = BulkQuoteRequestVendorItem::where('bulk_quote_request_vendor_id', $assignment->id)
            ->get()
            ->groupBy('variant_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'price' => $first && $first->quoted_unit_price !== null ? (float) $first->quoted_unit_price : null,
                    'gst' => $first && $first->gst_percentage !== null ? (float) $first->gst_percentage : 0,
                    'delivery' => $first && $first->delivery_charges !== null ? (float) $first->delivery_charges : 0,
                ];
            });

        $statusMap = $this->statusMap();

        return view('admin.bulk_quotes.vendor_view_bulk_quote', compact(
            'quote',
            'assignment',
            'statusMap',
            'suppliedVariants',
            'variantQuotes'
        ));
    }

    public function vendorResponseold(Request $request)
    {
        $request->validate([
            'bulk_quote_request_id' => 'required|integer|exists:bulk_quote_requests,id',
            'vendor_id' => 'required|integer|exists:vendors,id',
            'action' => 'required|string|in:accept,reject',
            'quoted_unit_price' => 'nullable|array',
            'quoted_unit_price.*' => 'nullable|numeric|min:0',
            'gst_percentage' => 'nullable|array',
            'gst_percentage.*' => 'nullable|numeric|min:0|max:100',
            'delivery_charges' => 'nullable|array',
            'delivery_charges.*' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date_format:d-m-Y|required_if:action,accept',
            'vendor_notes' => 'nullable|string|max:1000',
        ], [
            'expected_delivery_date.required_if' => 'Please provide the expected delivery date (dd-mm-yyyy) to accept this request.',
            'gst_percentage.*.numeric' => 'GST % must be a valid number.',
            'delivery_charges.*.numeric' => 'Delivery charges must be a valid number.',
        ]);

        try {
            DB::beginTransaction();

            $quote = BulkQuoteRequest::with([
                'items.variant.product.brand',
                'items.variant.product.category',
            ])->findOrFail($request->bulk_quote_request_id);

            $assignment = BulkQuoteRequestVendor::where('bulk_quote_request_id', $quote->id)
                ->where('vendor_id', $request->vendor_id)
                ->firstOrFail();

            $suppliedVariantIds = $this->vendorSuppliedVariantIds($quote, $request->vendor_id);
            $unitPrices = (array) $request->input('quoted_unit_price', []);
            $gstPercentages = (array) $request->input('gst_percentage', []);
            $deliveryCharges = (array) $request->input('delivery_charges', []);

            foreach ($suppliedVariantIds as $variantId) {
                $variantItems = $quote->items->where('variant_id', $variantId);
                $unitPrice = $unitPrices[$variantId] ?? null;
                $gst = $gstPercentages[$variantId] ?? 0;
                $del = $deliveryCharges[$variantId] ?? 0;

                if ($unitPrice === null || $unitPrice === '') {
                    foreach ($variantItems as $item) {
                        BulkQuoteRequestVendorItem::where('bulk_quote_request_vendor_id', $assignment->id)
                            ->where('bulk_quote_request_item_id', $item->id)
                            ->delete();
                    }
                    continue;
                }

                foreach ($variantItems as $item) {
                    BulkQuoteRequestVendorItem::updateOrCreate(
                        [
                            'bulk_quote_request_vendor_id' => $assignment->id,
                            'bulk_quote_request_item_id' => $item->id,
                        ],
                        [
                            'variant_id' => $item->variant_id,
                            'quoted_unit_price' => (float) $unitPrice,
                            'quoted_amount' => round((float) $unitPrice * (int) $item->qty, 2),
                            'gst_percentage' => (float) $gst,
                            'delivery_charges' => (float) $del,
                        ]
                    );
                }
            }

            $expectedDeliveryDate = $request->filled('expected_delivery_date')
                ? Carbon::createFromFormat('d-m-Y', $request->expected_delivery_date)->startOfDay()
                : null;

            if ($request->action === 'accept') {
                $assignment->status = 'Accepted';

                // Recalculate Grand Total matching the per-item taxation
                $totalQuoted = 0;
                $vendorItems = BulkQuoteRequestVendorItem::where('bulk_quote_request_vendor_id', $assignment->id)->get();
                foreach ($vendorItems as $vi) {
                    $base = (float) $vi->quoted_amount;
                    $gstAmt = round($base * ((float) $vi->gst_percentage / 100), 2);
                    $totalQuoted += ($base + $gstAmt + (float) $vi->delivery_charges);
                }

                $assignment->quoted_amount = $totalQuoted > 0 ? $totalQuoted : null;
                $assignment->expected_delivery_date = $expectedDeliveryDate;
                $assignment->gst_percentage = 0; // Nullified at top level as it's maintained per item
                $assignment->delivery_charges = 0; // Nullified at top level
            } else {
                $assignment->status = 'Declined';
                $assignment->quoted_amount = null;
                $assignment->expected_delivery_date = null;
                $assignment->gst_percentage = 0;
                $assignment->delivery_charges = 0;
            }

            $assignment->vendor_notes = $request->vendor_notes;
            $assignment->responded_at = Carbon::now();
            $assignment->save();

            if ($request->action === 'accept' && in_array($quote->status, ['Pending', 'Sent To Vendors'], true)) {
                $quote->status = 'Quoted';
                $quote->save();
            }

            DB::commit();

            $verb = $request->action === 'accept' ? 'accepted' : 'rejected';

            if ($request->action === 'accept') {
                $this->notifyCustomerForQuote(
                    $quote,
                    'Vendor Quote Received',
                    'A vendor has quoted ₹' . number_format($totalQuoted ?? 0, 2) . ' for your bulk quote request #ORD' . str_pad($quote->id, 5, '0', STR_PAD_LEFT) . '.',
                    'bulk_quote_received'
                );
            }

            return redirect()
                ->route('view_bulk_quote', $quote->id)
                ->with('success', "Vendor response recorded: request {$verb} successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record vendor response: ' . $e->getMessage());
        }
    }

    public function vendorResponse(Request $request)
    {
        $request->validate([
            'bulk_quote_request_id' => 'required|integer|exists:bulk_quote_requests,id',
            'vendor_id' => 'required|integer|exists:vendors,id',
            'action' => 'required|string|in:accept,reject',
            'quoted_unit_price' => 'nullable|array',
            'quoted_unit_price.*' => 'nullable|numeric|min:0',
            'gst_percentage' => 'nullable|array',
            'gst_percentage.*' => 'nullable|numeric|min:0|max:100',
            'delivery_charges' => 'nullable|array',
            'delivery_charges.*' => 'nullable|numeric|min:0',
            'expected_delivery_date' => 'nullable|date_format:d-m-Y|required_if:action,accept',
            'vendor_notes' => 'nullable|string|max:1000',
        ], [
            'expected_delivery_date.required_if' => 'Please provide the expected delivery date (dd-mm-yyyy) to accept this request.',
            'gst_percentage.*.numeric' => 'GST % must be a valid number.',
            'delivery_charges.*.numeric' => 'Delivery charges must be a valid number.',
        ]);

        try {
            DB::beginTransaction();

            $quote = BulkQuoteRequest::with([
                'items.variant.product.brand',
                'items.variant.product.category',
                'user',
            ])->findOrFail($request->bulk_quote_request_id);

            $assignment = BulkQuoteRequestVendor::with('vendor')
                ->where('bulk_quote_request_id', $quote->id)
                ->where('vendor_id', $request->vendor_id)
                ->firstOrFail();

            $suppliedVariantIds = $this->vendorSuppliedVariantIds($quote, $request->vendor_id);
            $unitPrices = (array) $request->input('quoted_unit_price', []);
            $gstPercentages = (array) $request->input('gst_percentage', []);
            $deliveryCharges = (array) $request->input('delivery_charges', []);

            foreach ($suppliedVariantIds as $variantId) {
                $variantItems = $quote->items->where('variant_id', $variantId);
                $unitPrice = $unitPrices[$variantId] ?? null;
                $gst = $gstPercentages[$variantId] ?? 0;
                $del = $deliveryCharges[$variantId] ?? 0;

                if ($unitPrice === null || $unitPrice === '') {
                    foreach ($variantItems as $item) {
                        BulkQuoteRequestVendorItem::where('bulk_quote_request_vendor_id', $assignment->id)
                            ->where('bulk_quote_request_item_id', $item->id)
                            ->delete();
                    }
                    continue;
                }

                foreach ($variantItems as $item) {
                    BulkQuoteRequestVendorItem::updateOrCreate(
                        [
                            'bulk_quote_request_vendor_id' => $assignment->id,
                            'bulk_quote_request_item_id' => $item->id,
                        ],
                        [
                            'variant_id' => $item->variant_id,
                            'quoted_unit_price' => (float) $unitPrice,
                            'quoted_amount' => round((float) $unitPrice * (int) $item->qty, 2),
                            'gst_percentage' => (float) $gst,
                            'delivery_charges' => (float) $del,
                        ]
                    );
                }
            }

            $expectedDeliveryDate = $request->filled('expected_delivery_date')
                ? Carbon::createFromFormat('d-m-Y', $request->expected_delivery_date)->startOfDay()
                : null;

            $totalQuoted = 0;
            $quotedItemCount = 0;

            if ($request->action === 'accept') {
                $assignment->status = 'Accepted';

                // Recalculate Grand Total matching the per-item taxation
                $vendorItems = BulkQuoteRequestVendorItem::where('bulk_quote_request_vendor_id', $assignment->id)->get();
                $quotedItemCount = $vendorItems->count();

                foreach ($vendorItems as $vi) {
                    $base = (float) $vi->quoted_amount;
                    $gstAmt = round($base * ((float) $vi->gst_percentage / 100), 2);
                    $totalQuoted += ($base + $gstAmt + (float) $vi->delivery_charges);
                }

                $assignment->quoted_amount = $totalQuoted > 0 ? $totalQuoted : null;
                $assignment->expected_delivery_date = $expectedDeliveryDate;
                $assignment->gst_percentage = 0; // Nullified at top level as it's maintained per item
                $assignment->delivery_charges = 0; // Nullified at top level
            } else {
                $assignment->status = 'Declined';
                $assignment->quoted_amount = null;
                $assignment->expected_delivery_date = null;
                $assignment->gst_percentage = 0;
                $assignment->delivery_charges = 0;
            }

            $assignment->vendor_notes = $request->vendor_notes;
            $assignment->responded_at = Carbon::now();
            $assignment->save();

            if ($request->action === 'accept' && in_array($quote->status, ['Pending', 'Sent To Vendors'], true)) {
                $quote->status = 'Quoted';
                $quote->save();
            }

            DB::commit();

            $verb = $request->action === 'accept' ? 'accepted' : 'rejected';
            $displayOrderId = '#ORD' . str_pad($quote->id, 5, '0', STR_PAD_LEFT);

            if ($request->action === 'accept') {
                $formattedTotal = '₹' . number_format((float) $totalQuoted, 2);
                $formattedDelivery = $expectedDeliveryDate
                    ? $expectedDeliveryDate->format('d M, Y')
                    : 'scheduled date';

                // 1. Vendor Quotation Received Notification
                $this->notifyCustomerForQuote(
                    $quote,
                    'New Vendor Quotation Received',
                    "A verified vendor has submitted a quote of {$formattedTotal} (incl. GST & delivery) across {$quotedItemCount} item(s) for your bulk request {$displayOrderId}.",
                    'bulk_quote_received'
                );

                // 2. Delivery & Review Action Notification
                $this->notifyCustomerForQuote(
                    $quote,
                    'Review & Lock Your Quote',
                    "Quote details for {$displayOrderId}: Estimated delivery by {$formattedDelivery}. Open your bulk quote screen to compare pricing and lock the best offer.",
                    'bulk_quote_received'
                );
            }

            return redirect()
                ->route('view_bulk_quote', $quote->id)
                ->with('success', "Vendor response recorded: request {$verb} successfully.");
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to record vendor response: ' . $e->getMessage());
        }
    }

    protected function vendorSuppliedVariantIds(BulkQuoteRequest $quote, $vendorId): array
    {
        $quoteVariantIds = $quote->variantIds();
        if (empty($quoteVariantIds)) {
            return [];
        }

        return VendorBulkProduct::where('vendor_id', $vendorId)
            ->whereIn('variant_id', $quoteVariantIds)
            ->where('status', 1)
            ->where('trash', 0)
            ->pluck('variant_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function vendorSuppliedItemIds(BulkQuoteRequest $quote, $vendorId): array
    {
        $suppliedVariantIds = $this->vendorSuppliedVariantIds($quote, $vendorId);
        return $quote->items
            ->filter(function ($item) use ($suppliedVariantIds) {
                return in_array((int) $item->variant_id, $suppliedVariantIds, true);
            })
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function notifyCustomerForQuote(BulkQuoteRequest $quote, string $title, string $message, string $type): void
    {
        try {
            $user = $quote->user ?? \App\Models\User::find($quote->user_id);
            if (!$user) {
                Log::warning("BulkQuoteAdminController: User not found for quote ID {$quote->id} (user_id: {$quote->user_id})");
                return;
            }

            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                $title,
                $message,
                $type
            );
        } catch (\Throwable $e) {
            Log::error('Bulk quote notification failed: ' . $e->getMessage());
        }
    }

    protected function eligibleVendorIdsByQuote($quotes): array
    {
        $quoteVariantIds = $quotes->mapWithKeys(function ($quote) {
            return [$quote->id => $quote->variantIds()];
        });

        $allVariantIds = $quoteVariantIds->flatten()->unique()->values()->all();

        $vendorIdsByVariant = VendorBulkProduct::whereIn('variant_id', $allVariantIds)
            ->where('status', 1)
            ->where('trash', 0)
            ->get(['vendor_id', 'variant_id'])
            ->groupBy('variant_id')
            ->map(function ($rows) {
                return $rows->pluck('vendor_id')->map(fn($id) => (int) $id)->unique()->values()->all();
            });

        return $quoteVariantIds->map(function ($variantIds) use ($vendorIdsByVariant) {
            $vendorIds = [];
            foreach ($variantIds as $variantId) {
                $matches = $vendorIdsByVariant->get($variantId);
                if (!empty($matches)) {
                    $vendorIds = array_merge($vendorIds, $matches);
                }
            }
            return array_values(array_unique($vendorIds));
        })->all();
    }
}