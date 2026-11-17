<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Engineer;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\DeliveryPartner;
use App\Models\DiscountCoupon;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class OrderAdminController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function manage(Request $request)
    {
        $query = Order::with(['user', 'address']);

        // Handle Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%$search%")
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

        $orders = $query->orderBy('id', 'desc')->paginate(10);

        // Fetch active entities for modals
        $engineers = Engineer::where('status', 1)->where('trash', 0)->get();
        $vendors = Vendor::where('status', 1)->where('trash', 0)->get();
        $deliveryPartners = DeliveryPartner::where('status', 1)->where('trash', 0)->get();

        $statusMap = [
            1 => 'In-Review',
            2 => 'Confirmed',
            3 => 'Completed',
            4 => 'Out for Delivery',
            5 => 'Cancelled',
            6 => 'Rejected',
            7 => 'Shipped',
            8 => 'Pending Rejected By Customer',
            9 => 'Customer Reject Approved',
        ];

        return view('admin.orders.manage_orders', compact('orders', 'engineers', 'vendors', 'deliveryPartners', 'statusMap'));
    }

    /**
     * Assign a Site Engineer to the Order
     */
    public function assignEngineer(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'engineer_id' => 'required|exists:engineers,id',
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            $order->engineer_id = $request->engineer_id;
            $order->save();

            return redirect()->back()->with('success', 'Site Engineer assigned successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to assign engineer: ' . $e->getMessage());
        }
    }

    /**
     * Assign a Vendor to the Order
     */
    public function assignVendor(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'vendor_id' => 'required|exists:vendors,id',
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            $order->vendor_id = $request->vendor_id;
            $order->save();

            return redirect()->back()->with('success', 'Vendor assigned successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to assign vendor: ' . $e->getMessage());
        }
    }

    /**
     * Update the Order Status
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|integer|in:1,2,3,4,5,6,7,8,9',
            'reject_reason' => 'required_if:status,6|nullable|string|max:500',
            'delivery_partner_id' => 'required_if:status,4|nullable|exists:delivery_partners,id'
        ], [
            'reject_reason.required_if' => 'A reason is required when rejecting an order.',
            'delivery_partner_id.required_if' => 'A delivery partner must be selected when status is Out for Delivery.'
        ]);

        try {
            DB::beginTransaction();

            $order = Order::findOrFail($request->order_id);

            // Only update and log if the status is actually changing
            if ($order->status != $request->status) {
                $order->status = $request->status;

                if ($request->status == 6) {
                    $order->cancel_reason = $request->reject_reason;
                }

                // Save delivery partner if status is 4 (Out for delivery)
                if ($request->status == 4 && $request->filled('delivery_partner_id')) {
                    $order->delivery_partner_id = $request->delivery_partner_id;
                }

                $order->save();

                // Log the exact timestamp this status was achieved
                DB::table('order_status_logs')->insert([
                    'order_id' => $order->id,
                    'status_code' => $request->status,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'Order status updated successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    /**
     * View detailed Order Information
     */
    public function view($id)
    {
        $order = Order::with(['items.product', 'items.variant', 'address', 'coupon', 'user', 'deliveryPartner'])->findOrFail($id);

        $assignedEngineer = Engineer::find($order->engineer_id);
        $assignedVendor = Vendor::find($order->vendor_id);

        $deliveryPartners = DeliveryPartner::where('status', 1)->where('trash', 0)->get();

        $statusMap = [
            1 => 'In-Review',
            2 => 'Confirmed',
            3 => 'Completed',
            4 => 'Out for Delivery',
            5 => 'Cancelled',
            6 => 'Rejected',
            7 => 'Shipped',
            8 => 'Pending Rejected By Customer',
            9 => 'Customer Reject Approved',
        ];

        return view('admin.orders.view_order', compact('order', 'assignedEngineer', 'assignedVendor', 'deliveryPartners', 'statusMap'));
    }

    // =========================================================================
    // NEW METHODS: QUANTITY VERIFICATION MANAGEMENT
    // =========================================================================

    /**
     * Display list of orders with pending quantity verifications
     */
    public function pendingQuantityVerifications()
    {
        $orders = Order::whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('order_qty_verification')
                ->whereRaw('order_qty_verification.order_id = orders.id')
                ->where('verification_status', 'Pending');
        })->with('user', 'items.product')->orderBy('updated_at', 'desc')->paginate(10);

        foreach ($orders as $order) {
            $order->pending_verifications = DB::table('order_qty_verification')
                ->join('products', 'order_qty_verification.product_id', '=', 'products.id')
                ->select('order_qty_verification.*', 'products.name as product_name')
                ->where('order_id', $order->id)
                ->where('verification_status', 'Pending')
                ->get();
        }

        return view('admin.orders.pending_qty_verifications', compact('orders'));
    }

    /**
     * Approve customer's quantity verification and recalculate pricing
     */
    public function approveQuantityVerificationold(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);

        DB::beginTransaction();
        try {
            $order = Order::with('items')->findOrFail($request->order_id);
            $verifications = DB::table('order_qty_verification')
                ->where('order_id', $order->id)
                ->where('verification_status', 'Pending')
                ->get();

            if ($verifications->isEmpty()) {
                return back()->with('error', 'No pending verifications found for this order.');
            }

            $newSubtotal = 0;
            $deliveredMap = [];
            foreach ($verifications as $v) {
                $deliveredMap[$v->item_id] = $v->delivered_qty;
            }

            foreach ($order->items as $item) {
                $deliveredQty = isset($deliveredMap[$item->id]) ? $deliveredMap[$item->id] : $item->qty;
                $newSubtotal += ($item->selling_price * $deliveredQty);

                // --- NEW LOGIC: Revert undelivered stock to inventory ---
                if ($item->qty > $deliveredQty) {
                    $stockDifference = $item->qty - $deliveredQty;

                    $variant = ProductVariant::find($item->variant_id);
                    if ($variant) {
                        $variant->stock += $stockDifference;
                        $variant->save();
                    }

                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock += $stockDifference;
                        $product->save();
                    }
                }
            }

            $newGrandTotal = ($newSubtotal + $order->tax_amount + $order->delivery_charges) - $order->discount_amount;
            if ($newGrandTotal < 0) {
                $newGrandTotal = 0;
            }

            $newPendingPayment = $newGrandTotal - $order->initial_payment;
            if ($newPendingPayment < 0) {
                $newPendingPayment = 0;
            }

            $order->subtotal = $newSubtotal;
            $order->grand_total = $newGrandTotal;
            $order->pending_payment = $newPendingPayment;
            $order->save();

            DB::table('order_qty_verification')
                ->where('order_id', $order->id)
                ->where('verification_status', 'Pending')
                ->update(['verification_status' => 'Approved', 'updated_at' => Carbon::now()]);

            foreach ($verifications as $v) {
                OrderItem::where('id', $v->item_id)->update([
                    'qty' => $v->delivered_qty,
                    'total_price' => DB::raw("selling_price * {$v->delivered_qty}")
                ]);
            }

            // --- RECORD TRANSACTION FOR VERIFICATION REFUND ---
            $refundAmount = $order->initial_payment - $newGrandTotal;
            if ($refundAmount > 0) {
                DB::table('transactions')->insert([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_status' => 'Pending', // Processing refund
                    'amount' => round($refundAmount, 2),
                    'payment_mode' => 'Online',
                    'transaction_type' => 'Credit', // Money goes back to user
                    'info' => 'Refund due to Quantity Discrepancy Approval',
                    'status' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            DB::commit();
            return back()->with('success', 'Quantity verified & updated. Stock reverted. New Pending Payment is ₹' . number_format($newPendingPayment, 2));
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Verification Failed: ' . $e->getMessage());
        }
    }

    /**
     * Approve customer's quantity verification and recalculate pricing
     */
    public function approveQuantityVerification(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);

        DB::beginTransaction();
        try {
            $order = Order::with('items')->findOrFail($request->order_id);
            $verifications = DB::table('order_qty_verification')
                ->where('order_id', $order->id)
                ->where('verification_status', 'Pending')
                ->get();

            if ($verifications->isEmpty()) {
                return back()->with('error', 'No pending verifications found for this order.');
            }

            $newSubtotal = 0;
            $deliveredMap = [];
            foreach ($verifications as $v) {
                $deliveredMap[$v->item_id] = $v->delivered_qty;
            }

            foreach ($order->items as $item) {
                $deliveredQty = isset($deliveredMap[$item->id]) ? $deliveredMap[$item->id] : $item->qty;
                $newSubtotal += ($item->selling_price * $deliveredQty);

                // --- LOGIC: Revert undelivered stock to inventory ---
                if ($item->qty > $deliveredQty) {
                    $stockDifference = $item->qty - $deliveredQty;

                    $variant = ProductVariant::find($item->variant_id);
                    if ($variant) {
                        $variant->stock += $stockDifference;
                        $variant->save();
                    }

                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->stock += $stockDifference;
                        $product->save();
                    }
                }
            }

            // --- CRITICAL FIX: Recalculate Tax & Delivery based on NEW Subtotal ---
            $newTaxAmount = $newSubtotal * 0.18; // Recalculate 18% GST based on delivered qty
            $newDeliveryCharges = ($newSubtotal < 10000) ? 500 : 0;

            // Recalculate Coupon if applicable (Percentage logic)
            $newCouponDiscountAmount = 0;
            if ($order->is_coupon_applied && $order->coupon_id) {
                $coupon = DiscountCoupon::find($order->coupon_id);
                if ($coupon) {
                    if (
                        ($coupon->coupon_type === 'Normal' && $newSubtotal >= $coupon->min_order_amount) ||
                        ($coupon->coupon_type === 'Bill Threshold' && $newSubtotal >= $coupon->threshold_amount)
                    ) {

                        if ($coupon->discount_type === 'Percentage') {
                            $newCouponDiscountAmount = ($newSubtotal * $coupon->discount_value) / 100;
                            if ($coupon->max_discount > 0 && $newCouponDiscountAmount > $coupon->max_discount) {
                                $newCouponDiscountAmount = $coupon->max_discount;
                            }
                        } else {
                            $newCouponDiscountAmount = $coupon->discount_value;
                        }
                    }
                }
            }

            $newGrandTotal = ($newSubtotal + $newTaxAmount + $newDeliveryCharges) - $newCouponDiscountAmount;
            if ($newGrandTotal < 0) {
                $newGrandTotal = 0;
            }

            $newPendingPayment = $newGrandTotal - $order->initial_payment;
            if ($newPendingPayment < 0) {
                $newPendingPayment = 0;
            }

            // Update Master Order Record
            $order->subtotal = $newSubtotal;
            $order->tax_amount = $newTaxAmount;
            $order->delivery_charges = $newDeliveryCharges;
            $order->discount_amount = $newCouponDiscountAmount;
            $order->grand_total = $newGrandTotal;
            $order->pending_payment = $newPendingPayment;
            $order->save();

            // Approve the Verifications in DB
            DB::table('order_qty_verification')
                ->where('order_id', $order->id)
                ->where('verification_status', 'Pending')
                ->update(['verification_status' => 'Approved', 'updated_at' => Carbon::now()]);

            // Update individual order items
            foreach ($verifications as $v) {
                OrderItem::where('id', $v->item_id)->update([
                    'qty' => $v->delivered_qty,
                    'total_price' => DB::raw("selling_price * {$v->delivered_qty}")
                ]);
            }

            // --- RECORD TRANSACTION FOR VERIFICATION REFUND ---
            $refundAmount = $order->initial_payment - $newGrandTotal;
            if ($refundAmount > 0) {
                DB::table('transactions')->insert([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_status' => 'Pending', // Processing refund
                    'amount' => round($refundAmount, 2),
                    'payment_mode' => 'Online',
                    'transaction_type' => 'Credit', // Money goes back to user
                    'info' => 'Refund due to Quantity Discrepancy Approval',
                    'status' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            DB::commit();
            return back()->with('success', 'Quantity verified & updated. Stock reverted. New Pending Payment is ₹' . number_format($newPendingPayment, 2));
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Verification Failed: ' . $e->getMessage());
        }
    }

    /**
     * Reject customer's quantity verification
     */
    public function rejectQuantityVerification(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reject_reason' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            DB::table('order_qty_verification')
                ->where('order_id', $request->order_id)
                ->where('verification_status', 'Pending')
                ->update(['verification_status' => 'Rejected', 'updated_at' => Carbon::now()]);

            $order = Order::findOrFail($request->order_id);

            if ($request->filled('reject_reason')) {
                $order->cancel_reason = "Qty Reject Note: " . $request->reject_reason;
                $order->save();
            }

            // Log zero-amount Info transaction as requested
            DB::table('transactions')->insert([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                'payment_status' => 'Completed',
                'amount' => 0.00,
                'payment_mode' => 'System',
                'transaction_type' => 'Info',
                'info' => 'Quantity Verification Rejected by Admin. No refund initiated.',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            DB::commit();
            return back()->with('success', 'Quantity verification rejected successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject verification: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // NEW METHODS: CUSTOMER REJECTION MANAGEMENT
    // =========================================================================

    /**
     * Display list of orders rejected by customers (Status 8)
     */
    public function pendingCustomerRejections()
    {
        $orders = Order::with('user')->where('status', 8)->orderBy('updated_at', 'desc')->paginate(10);
        return view('admin.orders.pending_customer_rejections', compact('orders'));
    }

    /**
     * Approve customer rejection (Change status to 9)
     */
    public function approveCustomerRejection(Request $request)
    {
        $request->validate(['order_id' => 'required|exists:orders,id']);

        DB::beginTransaction();
        try {
            $order = Order::with('items')->findOrFail($request->order_id);
            $order->status = 9;
            $order->save();

            // --- NEW LOGIC: Revert all items in this order to inventory ---
            foreach ($order->items as $item) {
                $variant = ProductVariant::find($item->variant_id);
                if ($variant) {
                    $variant->stock += $item->qty;
                    $variant->save();
                }

                $product = Product::find($item->product_id);
                if ($product) {
                    $product->stock += $item->qty;
                    $product->save();
                }
            }

            // --- RECORD TRANSACTION FOR CUSTOMER REJECTION REFUND ---
            if ($order->initial_payment > 0) {
                DB::table('transactions')->insert([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_status' => 'Pending', // Processing refund
                    'amount' => round($order->initial_payment, 2),
                    'payment_mode' => 'Online',
                    'transaction_type' => 'Credit', // Credit back to user
                    'info' => 'Refund for Customer Rejected Order (Approved)',
                    'status' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            DB::commit();
            return back()->with('success', 'Customer rejection approved successfully. Inventory stock has been restored.');
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error approving rejection: ' . $e->getMessage());
        }
    }

    /**
     * Deny customer rejection and revert to previous status
     */
    public function rejectCustomerRejection(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reject_reason' => 'nullable|string|max:500'
        ]);

        try {
            $order = Order::findOrFail($request->order_id);
            $order->status = 4; // Reverting back to Out for Delivery

            if ($request->filled('reject_reason')) {
                $order->cancel_reason = $order->cancel_reason . ' | Admin Denied Note: ' . $request->reject_reason;
            }
            $order->save();

            return back()->with('success', 'Customer rejection denied. Order reverted to Out for Delivery.');
        } catch (Exception $e) {
            return back()->with('error', 'Error rejecting customer request: ' . $e->getMessage());
        }
    }
}