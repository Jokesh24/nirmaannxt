<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BulkQuoteRequest;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class AdminBulkQuoteController extends Controller
{
    /**
     * Define the exact statuses required for Bulk Quotes.
     */
    protected $statuses = [
        'Pending' => 'Pending',
        'Vendor Assigned' => 'Vendor Assigned',
        'Accepted' => 'Accepted',
        'Rejected' => 'Rejected',
        'Shipped' => 'Shipped',
        'Out For Delivery' => 'Out For Delivery',
        'Completed' => 'Completed',
        'Canceled' => 'Canceled'
    ];

    /**
     * 1. Display a listing of the Bulk Quote Requests.
     */
    public function manage(Request $request)
    {
        $query = BulkQuoteRequest::with(['user', 'vendor']);

        // Handle Search (ID, Customer Name, Phone, GSTIN)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%$search%")
                    ->orWhere('full_name', 'like', "%$search%")
                    ->orWhere('phone_number', 'like', "%$search%")
                    ->orWhere('gstin', 'like', "%$search%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%$search%")
                            ->orWhere('mobile', 'like', "%$search%");
                    });
            });
        }

        // Handle Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Handle Date Filter
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
        $statusMap = $this->statuses;

        return view('admin.bulk_quotes.manage', compact('quotes', 'statusMap'));
    }

    /**
     * 2. View detailed Bulk Quote Information and Vendor Assignment Logic.
     */
    public function view($id)
    {
        $quote = BulkQuoteRequest::with([
            'items.variant.product.category',
            'user',
            'vendor'
        ])->findOrFail($id);

        $statusMap = $this->statuses;

        // Extract unique Category IDs from the items requested in this quote
        $categoryIds = $quote->items->map(function ($item) {
            return $item->variant->product->category_id ?? null;
        })->filter()->unique()->values()->toArray();

        // Fetch vendors whose 'products_supplied' includes ANY of the requested categories
        $vendorsQuery = Vendor::where('status', 'Active')->where('trash', 0);

        if (!empty($categoryIds)) {
            $vendorsQuery->where(function ($q) use ($categoryIds) {
                foreach ($categoryIds as $catId) {
                    $q->orWhereJsonContains('products_supplied', (string) $catId)
                        ->orWhereJsonContains('products_supplied', (int) $catId);
                }
            });
        }

        $eligibleVendors = $vendorsQuery->get();
        $assignedVendor = $quote->vendor;

        return view('admin.bulk_quotes.view', compact('quote', 'eligibleVendors', 'assignedVendor', 'statusMap', 'categoryIds'));
    }

    /**
     * 3. Assign a Vendor to the Bulk Quote
     */
    public function assignVendor(Request $request)
    {
        $request->validate([
            'quote_id' => 'required|exists:bulk_quote_requests,id',
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        try {
            $quote = BulkQuoteRequest::findOrFail($request->quote_id);
            $quote->vendor_id = $request->vendor_id;

            // Automatically update status to 'Vendor Assigned' if it's currently 'Pending'
            if ($quote->status === 'Pending') {
                $quote->status = 'Vendor Assigned';
            }

            $quote->save();

            return redirect()->back()->with('success', 'Vendor assigned to quote successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to assign vendor: ' . $e->getMessage());
        }
    }

    /**
     * 4. Update the Bulk Quote Status
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'quote_id' => 'required|exists:bulk_quote_requests,id',
            'status' => 'required|string|in:' . implode(',', array_keys($this->statuses)),
            'reject_reason' => 'required_if:status,Rejected,Canceled|nullable|string|max:500'
        ], [
            'reject_reason.required_if' => 'A reason is required when rejecting or canceling a quote.'
        ]);

        try {
            $quote = BulkQuoteRequest::findOrFail($request->quote_id);
            $quote->status = $request->status;

            // Assuming you have a cancel_reason or reject_reason column in bulk_quote_requests table
            if (in_array($request->status, ['Rejected', 'Canceled']) && $request->filled('reject_reason')) {
                $quote->cancel_reason = $request->reject_reason;
            }

            $quote->save();

            return redirect()->back()->with('success', 'Bulk Quote status updated to ' . $request->status . ' successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }
}