<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\CancelReason;
use App\Models\DiscountCoupon;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\PushNotificationTrait; // Imported the trait

class OrderController extends Controller
{
    use PushNotificationTrait; // Used the trait

    /**
     * Place a new order from the user's cart and calculate reward points.
     */
    public function placeOrderold(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer|exists:addresses,id',
            'coupon_id' => 'nullable|integer|exists:discount_coupons,id',
            'payment_type' => 'required|in:Initial Payment,Full Payment',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $address = Address::where('id', $request->address_id)->where('user_id', $user->id)->first();
        if (!$address) {
            return response()->json(['status' => false, 'message' => 'Invalid delivery address selected.'], 400);
        }

        $cartItems = Cart::with(['variant.product'])->where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Your cart is empty.'], 400);
        }

        try {
            DB::beginTransaction();

            $totalMrp = 0;
            $totalActivePrice = 0;
            $taxAmount = 0;
            $deliveryCharges = 0;
            $couponDiscountAmount = 0;
            $isCouponApplied = false;
            $totalEarnedPoints = 0;

            // 1. Calculate Base Totals & Variant-Based Reward Points (Using Order Type Logic)
            foreach ($cartItems as $item) {
                $variant = $item->variant;
                $isInstant = ($item->order_type == 2);

                $mrp = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_price : (float) $variant->price;
                $offerPrice = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_offer_price : (float) $variant->offer_price;
                $activePrice = $offerPrice > 0 ? $offerPrice : $mrp;

                $itemActiveTotal = $activePrice * $item->qty;
                $royaltyPercent = (float) ($variant->royalty_percentage ?? 0);
                $itemPoints = $royaltyPercent > 0 ? ($itemActiveTotal * ($royaltyPercent / 100)) : 0;

                $totalMrp += ($mrp * $item->qty);
                $totalActivePrice += $itemActiveTotal;
                $totalEarnedPoints += $itemPoints;
            }

            // 2. Base Math Logic
            $totalItemDiscount = $totalMrp - $totalActivePrice;
            $taxAmount = $totalActivePrice * 0.18; // 18% GST

            // 3. Delivery Logic
            $deliveryCharges = 0;

            // 4. Apply Coupon Logic
            $couponId = $request->coupon_id ?? $cartItems->first()->coupon_id;

            if ($couponId) {
                $today = Carbon::now()->format('Y-m-d');
                $coupon = DiscountCoupon::where('id', $couponId)
                    ->where('status', 1)
                    ->where('trash', 0)
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->first();

                if ($coupon) {
                    $isEligible = false;
                    if ($coupon->coupon_type === 'Normal' && $totalActivePrice >= $coupon->min_order_amount) {
                        $isEligible = true;
                    } elseif ($coupon->coupon_type === 'Bill Threshold' && $totalActivePrice >= $coupon->threshold_amount) {
                        $isEligible = true;
                    }

                    if ($isEligible) {
                        $isCouponApplied = true;
                        if ($coupon->discount_type === 'Percentage') {
                            $couponDiscountAmount = ($totalActivePrice * $coupon->discount_value) / 100;
                            if ($coupon->max_discount > 0 && $couponDiscountAmount > $coupon->max_discount) {
                                $couponDiscountAmount = $coupon->max_discount;
                            }
                        } else {
                            $couponDiscountAmount = $coupon->discount_value;
                        }
                    } else {
                        return response()->json(['status' => false, 'message' => 'Cart does not meet the minimum requirements for this coupon.'], 400);
                    }
                }
            }

            // 5. Calculate Final Master Totals
            $totalSavings = $totalItemDiscount + $couponDiscountAmount;
            $grandTotal = $totalActivePrice - $couponDiscountAmount + $taxAmount + $deliveryCharges;

            $initialPayment = 0;
            $pendingPayment = 0;

            if ($request->payment_type === 'Initial Payment') {
                $initialPayment = ($grandTotal >= 10000) ? 10000 : ($grandTotal * 0.20);
                $pendingPayment = $grandTotal - $initialPayment;
            } else {
                $initialPayment = $grandTotal;
                $pendingPayment = 0;
            }

            // 6. Create Order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'order_type' => (int) $cartItems->first()->order_type,
                'subtotal' => round($totalActivePrice, 2),
                'tax_amount' => round($taxAmount, 2),
                'delivery_charges' => round($deliveryCharges, 2),
                'is_coupon_applied' => $isCouponApplied ? 1 : 0,
                'coupon_id' => $isCouponApplied ? $couponId : null,
                'discount_amount' => round($couponDiscountAmount, 2),
                'grand_total' => round($grandTotal, 2),
                'initial_payment' => round($initialPayment, 2),
                'pending_payment' => round($pendingPayment, 2),
                'payment_type' => $request->payment_type,
                'payment_status' => 'Pending',
                'delivery_otp' => rand(1000, 9999),
                'status' => 1
            ]);

            // --- 6.1 RECORD TRANSACTION FOR PLACE ORDER ---
            if ($initialPayment > 0) {
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_status' => 'Completed',
                    'amount' => round($initialPayment, 2),
                    'payment_mode' => 'Online', // Standard for initial/full upfront
                    'transaction_type' => 'Debit', // User pays money
                    'info' => "Payment for Order Placement ({$request->payment_type})",
                    'status' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            // 7. Create Order Items
            $orderItemsData = [];
            foreach ($cartItems as $item) {
                $variant = $item->variant;
                $isInstant = ($item->order_type == 2);

                $mrp = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_price : (float) $variant->price;
                $offerPrice = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_offer_price : (float) $variant->offer_price;
                $activePrice = $offerPrice > 0 ? $offerPrice : $mrp;

                $orderItemsData[] = [
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'order_type' => $item->order_type,
                    'qty' => $item->qty,
                    'price' => $mrp,
                    'offer_price' => $offerPrice,
                    'selling_price' => $activePrice,
                    'total_price' => $activePrice * $item->qty,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            OrderItem::insert($orderItemsData);

            // 8. Reward Points Logic (Based on each variant's royalty_percentage)
            $earnedPoints = round((float) $totalEarnedPoints, 2);
            if ($earnedPoints > 0) {
                DB::table('reward_points_transactions')->insert([
                    'user_id' => $user->id,
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                    'transaction_type' => 'Credit',
                    'points' => $earnedPoints,
                    'description' => 'Order Commission',
                    'status' => 'Pending',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $userPoints = DB::table('user_reward_points')->where('user_id', $user->id)->first();
                if ($userPoints) {
                    DB::table('user_reward_points')->where('user_id', $user->id)->update([
                        'total_points' => DB::raw("total_points + $earnedPoints"),
                        'total_earned' => DB::raw("total_earned + $earnedPoints"),
                        'updated_at' => Carbon::now()
                    ]);
                } else {
                    DB::table('user_reward_points')->insert([
                        'user_id' => $user->id,
                        'total_points' => $earnedPoints,
                        'total_earned' => $earnedPoints,
                        'tier_status' => 'Bronze',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
            }

            // 9. Clear Cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            // Send detailed order confirmation with payment and reward-points breakdown.
            $notificationMessage = "Your order {$order->order_number} has been placed successfully. "
                . "Order total: Rs. " . number_format((float) $grandTotal, 2) . ". "
                . "Initial payment received: Rs. " . number_format((float) $initialPayment, 2) . ". "
                . "Pending amount: Rs. " . number_format((float) $pendingPayment, 2) . ". "
                . "Payment type: {$request->payment_type}. "
                . "Reward points earned: {$earnedPoints}. These points are currently pending and will be subject to order completion and verification.";

            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Order Placed Successfully",
                $notificationMessage,
                'order_tracking'
            );

            $order->load(['items.product', 'items.variant', 'address']);

            $formattedItems = $order->items->map(function ($orderItem) {
                $variant = $orderItem->variant;
                $attributeText = is_array($variant->attributes) ? implode(', ', $variant->attributes) : ($variant->attributes ?? '1 Unit');
                $subtitle = "{$attributeText} per {$variant->unit} • ₹{$orderItem->selling_price} each";

                return [
                    'productName' => $orderItem->product->name,
                    'subtitle' => $subtitle,
                    'qty' => $orderItem->qty,
                    'orderType' => $orderItem->order_type,
                    'sellingPrice' => round($orderItem->selling_price, 2),
                    'itemTotal' => round($orderItem->total_price, 2),
                    'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully.',
                'data' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'orderStatus' => 'Your Order is Placed',
                    'statusMessage' => 'Waiting for Site Engineer Verification',
                    'paymentType' => $order->payment_type,
                    'amountToPayNow' => round($order->initial_payment, 2),
                    'pendingAmount' => round($order->pending_payment, 2),
                    'totalSavings' => round($totalSavings, 2),
                    'earnedPoints' => $earnedPoints,
                    'deliveryAddress' => [
                        'tag' => $order->address->site_name,
                        'addressLine' => $order->address->delivery_address,
                    ],
                    'items' => $formattedItems
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to place order. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Place a new order from the user's cart and calculate reward points.
     */
    public function placeOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|integer|exists:addresses,id',
            'coupon_id' => 'nullable|integer|exists:discount_coupons,id',
            'payment_type' => 'required|in:Initial Payment,Full Payment',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $address = Address::where('id', $request->address_id)->where('user_id', $user->id)->first();
        if (!$address) {
            return response()->json(['status' => false, 'message' => 'Invalid delivery address selected.'], 400);
        }

        $cartItems = Cart::with(['variant.product'])->where('user_id', $user->id)->get();
        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Your cart is empty.'], 400);
        }

        try {
            DB::beginTransaction();

            $totalMrp = 0;
            $totalActivePrice = 0;
            $taxAmount = 0;
            $deliveryCharges = 0;
            $couponDiscountAmount = 0;
            $isCouponApplied = false;
            $totalEarnedPoints = 0;

            // 1. Calculate Base Totals & Variant-Based Reward Points (Using Order Type Logic)
            foreach ($cartItems as $item) {
                $variant = $item->variant;
                $isInstant = ($item->order_type == 2);

                $mrp = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_price : (float) $variant->price;
                $offerPrice = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_offer_price : (float) $variant->offer_price;
                $activePrice = $offerPrice > 0 ? $offerPrice : $mrp;

                $itemActiveTotal = $activePrice * $item->qty;
                $royaltyPercent = (float) ($variant->royalty_percentage ?? 0);
                $itemPoints = $royaltyPercent > 0 ? ($itemActiveTotal * ($royaltyPercent / 100)) : 0;

                $totalMrp += ($mrp * $item->qty);
                $totalActivePrice += $itemActiveTotal;
                $totalEarnedPoints += $itemPoints;
            }

            // 2. Base Math Logic
            $totalItemDiscount = $totalMrp - $totalActivePrice;
            $taxAmount = $totalActivePrice * 0.18; // 18% GST

            // 3. Delivery Logic
            $deliveryCharges = 0;

            // 4. Apply Coupon Logic
            $couponId = $request->coupon_id ?? $cartItems->first()->coupon_id;

            if ($couponId) {
                $today = Carbon::now()->format('Y-m-d');
                $coupon = DiscountCoupon::where('id', $couponId)
                    ->where('status', 1)
                    ->where('trash', 0)
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->first();

                if ($coupon) {
                    $isEligible = false;
                    if ($coupon->coupon_type === 'Normal' && $totalActivePrice >= $coupon->min_order_amount) {
                        $isEligible = true;
                    } elseif ($coupon->coupon_type === 'Bill Threshold' && $totalActivePrice >= $coupon->threshold_amount) {
                        $isEligible = true;
                    }

                    if ($isEligible) {
                        $isCouponApplied = true;
                        if ($coupon->discount_type === 'Percentage') {
                            $couponDiscountAmount = ($totalActivePrice * $coupon->discount_value) / 100;
                            if ($coupon->max_discount > 0 && $couponDiscountAmount > $coupon->max_discount) {
                                $couponDiscountAmount = $coupon->max_discount;
                            }
                        } else {
                            $couponDiscountAmount = $coupon->discount_value;
                        }
                    } else {
                        return response()->json(['status' => false, 'message' => 'Cart does not meet the minimum requirements for this coupon.'], 400);
                    }
                }
            }

            // 5. Calculate Final Master Totals
            $totalSavings = $totalItemDiscount + $couponDiscountAmount;
            $grandTotal = $totalActivePrice - $couponDiscountAmount + $taxAmount + $deliveryCharges;

            $initialPayment = 0;
            $pendingPayment = 0;

            if ($request->payment_type === 'Initial Payment') {
                $initialPayment = ($grandTotal >= 10000) ? 10000 : ($grandTotal * 0.20);
                $pendingPayment = $grandTotal - $initialPayment;
            } else {
                $initialPayment = $grandTotal;
                $pendingPayment = 0;
            }

            // 6. Create Order
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'user_id' => $user->id,
                'address_id' => $address->id,
                'order_type' => (int) $cartItems->first()->order_type,
                'subtotal' => round($totalActivePrice, 2),
                'tax_amount' => round($taxAmount, 2),
                'delivery_charges' => round($deliveryCharges, 2),
                'is_coupon_applied' => $isCouponApplied ? 1 : 0,
                'coupon_id' => $isCouponApplied ? $couponId : null,
                'discount_amount' => round($couponDiscountAmount, 2),
                'grand_total' => round($grandTotal, 2),
                'initial_payment' => round($initialPayment, 2),
                'pending_payment' => round($pendingPayment, 2),
                'payment_type' => $request->payment_type,
                'payment_status' => 'Pending',
                'delivery_otp' => rand(1000, 9999),
                'status' => 1
            ]);

            // --- 6.1 RECORD TRANSACTION FOR PLACE ORDER ---
            if ($initialPayment > 0) {
                DB::table('transactions')->insert([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'transaction_id' => 'TXN-' . strtoupper(uniqid()),
                    'payment_status' => 'Completed',
                    'amount' => round($initialPayment, 2),
                    'payment_mode' => 'Online', // Standard for initial/full upfront
                    'transaction_type' => 'Debit', // User pays money
                    'info' => "Payment for Order Placement ({$request->payment_type})",
                    'status' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            // 7. Create Order Items
            $orderItemsData = [];
            foreach ($cartItems as $item) {
                $variant = $item->variant;
                $isInstant = ($item->order_type == 2);

                $mrp = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_price : (float) $variant->price;
                $offerPrice = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_offer_price : (float) $variant->offer_price;
                $activePrice = $offerPrice > 0 ? $offerPrice : $mrp;

                $orderItemsData[] = [
                    'order_id' => $order->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'order_type' => $item->order_type,
                    'qty' => $item->qty,
                    'price' => $mrp,
                    'offer_price' => $offerPrice,
                    'selling_price' => $activePrice,
                    'total_price' => $activePrice * $item->qty,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            OrderItem::insert($orderItemsData);

            // 8. Reward Points Logic (Based on each variant's royalty_percentage)
            $earnedPoints = round((float) $totalEarnedPoints, 2);
            if ($earnedPoints > 0) {
                DB::table('reward_points_transactions')->insert([
                    'user_id' => $user->id,
                    'reference_id' => $order->id,
                    'reference_type' => Order::class,
                    'transaction_type' => 'Credit',
                    'points' => $earnedPoints,
                    'description' => 'Order Commission',
                    'status' => 'Pending',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);

                $userPoints = DB::table('user_reward_points')->where('user_id', $user->id)->first();
                if ($userPoints) {
                    DB::table('user_reward_points')->where('user_id', $user->id)->update([
                        'total_points' => DB::raw("total_points + $earnedPoints"),
                        'total_earned' => DB::raw("total_earned + $earnedPoints"),
                        'updated_at' => Carbon::now()
                    ]);
                } else {
                    DB::table('user_reward_points')->insert([
                        'user_id' => $user->id,
                        'total_points' => $earnedPoints,
                        'total_earned' => $earnedPoints,
                        'tier_status' => 'Bronze',
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now()
                    ]);
                }
            }

            // 9. Clear Cart
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            // Format amounts cleanly
            $formattedGrandTotal = '₹' . number_format((float) $grandTotal, 2);
            $formattedInitialPayment = '₹' . number_format((float) $initialPayment, 2);
            $formattedPendingPayment = '₹' . number_format((float) $pendingPayment, 2);

            // 1. Order Confirmation Notification
            $orderNotificationMessage = "Your order {$order->order_number} for {$formattedGrandTotal} has been placed and is awaiting Site Engineer verification.";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Order Placed Successfully",
                $orderNotificationMessage,
                'order_tracking'
            );

            // 2. Payment Summary Notification
            $paymentNotificationMessage = ($pendingPayment > 0)
                ? "Received {$formattedInitialPayment} ({$request->payment_type}) for order {$order->order_number}. Pending balance: {$formattedPendingPayment}."
                : "Full payment of {$formattedInitialPayment} received for order {$order->order_number}. No balance due.";

            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Payment Received",
                $paymentNotificationMessage,
                'order_tracking'
            );

            // 3. Reward Points Notification
            if ($earnedPoints > 0) {
                $pointsNotificationMessage = "You earned {$earnedPoints} Reward Points on order {$order->order_number}. Points will be credited upon order completion.";
                $this->sendAndSaveNotification(
                    $user->id,
                    $user->device_token,
                    "Reward Points Earned",
                    $pointsNotificationMessage,
                    'order_tracking'
                );
            }

            $order->load(['items.product', 'items.variant', 'address']);

            $formattedItems = $order->items->map(function ($orderItem) {
                $variant = $orderItem->variant;
                $attributeText = is_array($variant->attributes) ? implode(', ', $variant->attributes) : ($variant->attributes ?? '1 Unit');
                $subtitle = "{$attributeText} per {$variant->unit} • ₹{$orderItem->selling_price} each";

                return [
                    'productName' => $orderItem->product->name,
                    'subtitle' => $subtitle,
                    'qty' => $orderItem->qty,
                    'orderType' => $orderItem->order_type,
                    'sellingPrice' => round($orderItem->selling_price, 2),
                    'itemTotal' => round($orderItem->total_price, 2),
                    'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Order placed successfully.',
                'data' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'orderStatus' => 'Your Order is Placed',
                    'statusMessage' => 'Waiting for Site Engineer Verification',
                    'paymentType' => $order->payment_type,
                    'amountToPayNow' => round($order->initial_payment, 2),
                    'pendingAmount' => round($order->pending_payment, 2),
                    'totalSavings' => round($totalSavings, 2),
                    'earnedPoints' => $earnedPoints,
                    'deliveryAddress' => [
                        'tag' => $order->address->site_name,
                        'addressLine' => $order->address->delivery_address,
                    ],
                    'items' => $formattedItems
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to place order. Please try again.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel Order
     */
    /**
     * Cancel Order
     */
    public function cancelOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id',
            'cancelling_reason_id' => 'required|integer|exists:cancel_reasons,id',
            'reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $order = Order::with('items')->where('id', $request->orderId)->where('user_id', $user->id)->first();
        if (!$order || $order->status != 1) {
            return response()->json(['status' => false, 'message' => 'Order cannot be cancelled at this stage.'], 400);
        }

        try {
            DB::beginTransaction();

            $order->status = 5;
            $order->cancelling_reason_id = $request->cancelling_reason_id;
            $order->cancel_reason = $request->reason;
            $order->save();

            // Restore Inventory
            foreach ($order->items as $item) {
                $variant = \App\Models\ProductVariant::find($item->variant_id);
                if ($variant) {
                    $variant->stock += $item->qty;
                    $variant->save();
                }

                $product = \App\Models\Product::find($item->product_id);
                if ($product) {
                    $product->stock += $item->qty;
                    $product->save();
                }
            }

            // --- RECORD TRANSACTION FOR CANCELLATION REFUND ---
            $refundTransactionId = null;
            if ($order->initial_payment > 0) {
                $refundTransactionId = 'TXN-' . strtoupper(uniqid());
                DB::table('transactions')->insert([
                    'user_id' => $order->user_id,
                    'order_id' => $order->id,
                    'transaction_id' => $refundTransactionId,
                    'payment_status' => 'Pending', // Pending processing from bank
                    'amount' => round($order->initial_payment, 2),
                    'payment_mode' => 'Online',
                    'transaction_type' => 'Credit', // Refund back to user
                    'info' => 'Refund initiated for Cancelled Order',
                    'status' => 1,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ]);
            }

            DB::commit();

            // 1. Order Cancellation Notification
            $cancelNotificationMessage = "Your order {$order->order_number} has been cancelled. Reason: {$order->cancel_reason}.";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Order Cancelled",
                $cancelNotificationMessage,
                'order_tracking'
            );

            // 2. Refund Status Notification
            $refundAmount = (float) $order->initial_payment;
            if ($refundAmount > 0) {
                $formattedRefund = '₹' . number_format($refundAmount, 2);
                $refundNotificationMessage = "A refund of {$formattedRefund} (Ref: {$refundTransactionId}) for cancelled order {$order->order_number} has been initiated and will be credited to your original payment method within 3-4 business days.";
                $this->sendAndSaveNotification(
                    $user->id,
                    $user->device_token,
                    "Refund Initiated",
                    $refundNotificationMessage,
                    'order_tracking'
                );
            } else {
                $refundNotificationMessage = "No upfront payment was charged for cancelled order {$order->order_number}, so no refund is required.";
                $this->sendAndSaveNotification(
                    $user->id,
                    $user->device_token,
                    "Refund Update",
                    $refundNotificationMessage,
                    'order_tracking'
                );
            }

            return response()->json(['status' => true, 'message' => 'Order has been cancelled successfully and inventory stock has been restored.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to cancel order.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Process Pending Payment
     */
    public function payPendingAmountold(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id',
            'paymentMethod' => 'required|in:Online,COD'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('id', $request->orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->pending_payment <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'No pending payment left for this order.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $amountPaid = $order->pending_payment;
            $transactionId = 'TIDN' . $order->id . time();

            // --- RECORD TRANSACTION FOR PENDING BALANCE PAYMENT ---
            DB::table('transactions')->insert([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'Completed',
                'amount' => round($amountPaid, 2),
                'payment_mode' => $request->paymentMethod,
                'transaction_type' => 'Debit', // User pays money
                'info' => 'Payment for Pending Order Balance',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            $order->payment_status = 'Completed';
            $order->pending_payment = 0;
            $order->status = 3;
            $order->save();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Payment processed and Order Delivered Successfully.',
                'data' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'paymentType' => $order->payment_type,
                    'paymentMethod' => $request->paymentMethod,
                    'transactionId' => $transactionId,
                    'dateAndTime' => Carbon::now()->format('d M, Y, h:i A'),
                    'amountPaid' => round($order->grand_total, 2)
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Payment processing failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process Pending Payment
     */
    /**
     * Process Pending Payment
     */
    public function payPendingAmount(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id',
            'paymentMethod' => 'required|in:Online,COD'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('id', $request->orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->pending_payment <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'No pending payment left for this order.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $amountPaid = $order->pending_payment;
            $transactionId = 'TIDN' . $order->id . time();

            // --- RECORD TRANSACTION FOR PENDING BALANCE PAYMENT ---
            DB::table('transactions')->insert([
                'user_id' => $order->user_id,
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'payment_status' => 'Completed',
                'amount' => round($amountPaid, 2),
                'payment_mode' => $request->paymentMethod,
                'transaction_type' => 'Debit', // User pays money
                'info' => 'Payment for Pending Order Balance',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            $order->payment_status = 'Completed';
            $order->pending_payment = 0;
            $order->status = 3;
            $order->save();

            DB::commit();

            // Format amounts cleanly
            $formattedAmountPaid = '₹' . number_format((float) $amountPaid, 2);
            $formattedGrandTotal = '₹' . number_format((float) $order->grand_total, 2);

            // 1. Balance Payment Receipt Notification
            $paymentNotificationMessage = "Pending balance of {$formattedAmountPaid} ({$request->paymentMethod}) received for order {$order->order_number} (Txn ID: {$transactionId}). Total order value {$formattedGrandTotal} is now fully settled.";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Pending Balance Paid",
                $paymentNotificationMessage,
                'order_tracking'
            );

            // 2. Order Delivered / Completed Notification
            $deliveryNotificationMessage = "Your order {$order->order_number} has been delivered and marked as completed. Thank you for ordering with NirmaanNXT!";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Order Delivered",
                $deliveryNotificationMessage,
                'order_tracking'
            );

            return response()->json([
                'status' => true,
                'message' => 'Payment processed and Order Delivered Successfully.',
                'data' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'paymentType' => $order->payment_type,
                    'paymentMethod' => $request->paymentMethod,
                    'transactionId' => $transactionId,
                    'dateAndTime' => Carbon::now()->format('d M, Y, h:i A'),
                    // FIX: Show exact pending amount paid for this specific transaction, not the grand total.
                    'amountPaid' => round($amountPaid, 2),
                    'grandTotal' => round($order->grand_total, 2)
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Payment processing failed.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function payPendingAmountjok(Request $request)
    {
        return $this->payPendingAmount($request);
    }

    /**
     * Get My Orders (List View)
     */
    public function getMyOrders(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $orders = Order::with(['address', 'items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No orders found.',
                'data' => []
            ], 200);
        }

        $statusMap = [
            1 => 'In-Review',
            2 => 'Confirmed',
            3 => 'Completed',
            4 => 'Out for Delivery',
            5 => 'Cancelled',
            6 => 'Rejected',
            7 => 'Shipped',
            8 => 'Customer Rejected',
            9 => 'Reject Approved',
        ];

        $formattedOrders = $orders->map(function ($order) use ($statusMap) {
            $formattedItems = $order->items->map(function ($item) {
                $variant = $item->variant;
                $product = $item->product;
                $unit = $item ? $item->unit : 'Units';
                $qty = $item ? $item->qty : 0;
                $qtyText = "{$qty}";

                return [
                    'itemId' => $item->id,
                    'productName' => $product ? $product->name : 'Unknown Product',
                    'price' => (float) $item->total_price,
                    'qtyText' => $qtyText,
                    'orderType' => $item->order_type,
                    'image' => ($variant && $variant->image) ? asset('uploads/products/' . $variant->image) : null,
                ];
            });

            $siteName = 'My Site';
            if ($order->address) {
                $siteName = $order->address->site_name ?: $order->address->address_title;
            }

            $expectedDelivery = Carbon::parse($order->created_at)->addDays(3)->format('d M, Y');
            $updatedDate = Carbon::parse($order->updated_at)->format('d M, Y');

            if ($order->status == 3) {
                $deliveryText = "Delivered on {$updatedDate}";
            } elseif ($order->status == 5) {
                $deliveryText = "Cancelled on {$updatedDate}";
            } elseif (in_array($order->status, [6, 8, 9])) {
                $deliveryText = "Rejected on {$updatedDate}";
            } else {
                $deliveryText = "Delivery expected on {$expectedDelivery}";
            }

            return [
                'orderId' => $order->id,
                'orderNumber' => $order->order_number,
                'orderType' => $order->order_type,
                'purchasedFor' => $siteName,
                'statusBadge' => $statusMap[$order->status] ?? 'Unknown',
                'statusCode' => (int) $order->status,
                'isCancellable' => $order->status == 1,
                'deliveryText' => $deliveryText,
                'items' => $formattedItems
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => [
                'orders' => $formattedOrders
            ]
        ], 200);
    }

    /**
     * Get Order Details
     */
    public function getOrderDetails(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::with(['items.product', 'items.variant', 'address', 'coupon', 'engineer', 'deliveryPartner'])
            ->where('id', $request->orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found or unauthorized.'
            ], 404);
        }

        // ----------------------------------------------------------------------
        // 1. UI Status Mapping & Screen Configuration
        // ----------------------------------------------------------------------
        $screenConfig = [
            'headerTitle' => 'Order Placed!',
            'headerSubtitle' => null,
            'showTimeline' => true,
            'showSiteEngineer' => false,
            'showDriver' => false,
            'showOtp' => false,
            'showLiveTracking' => false,
            'showBillSummary' => true,
            'showDeliveryAddress' => true,
        ];

        $isPlaced = true;
        $isShipped = false;
        $isOutForDelivery = false;
        $isDelivered = false;

        switch ($order->status) {
            case 2:
                $screenConfig['headerTitle'] = 'Order Confirmed!';
                break;
            case 7:
                $screenConfig['headerTitle'] = 'Order is Shipped!';
                $screenConfig['showSiteEngineer'] = true;
                $isShipped = true;
                break;
            case 4:
                $screenConfig['headerTitle'] = 'Your Order is on the way!';
                $screenConfig['headerSubtitle'] = 'Order ID: ' . $order->order_number;
                $screenConfig['showSiteEngineer'] = true;
                $screenConfig['showDriver'] = true;
                $screenConfig['showOtp'] = true;
                $screenConfig['showLiveTracking'] = true;

                $screenConfig['primaryButton'] = ['text' => 'Verify Quantity', 'action' => 'VERIFY_QTY'];
                $screenConfig['secondaryButton'] = ['text' => 'Reject Order', 'action' => 'REJECT_ORDER'];

                $isShipped = true;
                $isOutForDelivery = true;
                break;
            case 3:
                $screenConfig['headerTitle'] = 'Order Delivered!';
                $isShipped = true;
                $isOutForDelivery = true;
                $isDelivered = true;
                break;
            case 5:
                $screenConfig['headerTitle'] = 'Order Cancelled';
                $screenConfig['showTimeline'] = false;
                break;
            case 6:
                $screenConfig['headerTitle'] = 'Order Rejected';
                $screenConfig['showTimeline'] = false;
                break;
        }

        // ----------------------------------------------------------------------
        // 2. Timeline Formatting (Dynamic Status History)
        // ----------------------------------------------------------------------
        $statusLogs = DB::table('order_status_logs')
            ->where('order_id', $order->id)
            ->pluck('created_at', 'status_code');

        $placedDate = isset($statusLogs[1]) ? Carbon::parse($statusLogs[1])->format('d M, Y') : Carbon::parse($order->created_at)->format('d M, Y');
        $shippedDate = isset($statusLogs[7]) ? Carbon::parse($statusLogs[7])->format('d M, Y') : null;
        $outForDeliveryDate = isset($statusLogs[4]) ? Carbon::parse($statusLogs[4])->format('d M, Y') : null;
        $deliveredDate = isset($statusLogs[3]) ? Carbon::parse($statusLogs[3])->format('d M, Y') : null;

        $timeline = [
            ['title' => 'Order Placed', 'date' => $placedDate, 'isCompleted' => $isPlaced],
            ['title' => 'Shipped', 'date' => $shippedDate, 'isCompleted' => $isShipped],
            ['title' => 'Out for Delivery', 'date' => $outForDeliveryDate, 'isCompleted' => $isOutForDelivery],
            ['title' => 'Delivered', 'date' => $deliveredDate, 'isCompleted' => $isDelivered]
        ];

        // ----------------------------------------------------------------------
        // 3. Personnel Data 
        // ----------------------------------------------------------------------
        $siteEngineer = null;
        if ($screenConfig['showSiteEngineer'] && $order->engineer) {
            $siteEngineer = [
                'siteEngineerId' => $order->engineer->engineer_id ?? $order->engineer->id,
                'name' => $order->engineer->full_name,
                'role' => 'Site Engineer',
                'phone' => $order->engineer->mobile_number,
                'image' => $order->engineer->profile_photo ? asset('uploads/engineer_profiles/' . $order->engineer->profile_photo) : null
            ];
        }

        $driver = null;
        if ($screenConfig['showDriver'] && $order->deliveryPartner) {
            $driver = [
                'deliveryPartnerId' => $order->deliveryPartner->driver_id ?? $order->deliveryPartner->id,
                'name' => $order->deliveryPartner->driver_name,
                'vehicleNo' => $order->deliveryPartner->vehicle_reg_number,
                'vehicleType' => $order->deliveryPartner->vehicle_type,
                'phone' => $order->deliveryPartner->mobile_number,
                'image' => $order->deliveryPartner->profile_photo ? asset('uploads/delivery_partners/' . $order->deliveryPartner->profile_photo) : null
            ];
        }

        // ----------------------------------------------------------------------
        // 4. Format Order Items
        // ----------------------------------------------------------------------
        $formattedItems = $order->items->map(function ($orderItem) {
            $variant = $orderItem->variant;
            $product = $orderItem->product;

            $attributeText = '1 Unit';
            if ($variant && !empty($variant->attributes)) {
                $attributeText = is_array($variant->attributes) ? implode(', ', $variant->attributes) : $variant->attributes;
            }
            $subtitle = "{$attributeText} • ₹" . (float) $orderItem->selling_price . " each";
            $unit = $orderItem ? $orderItem->unit : 'Units';
            $qty = $orderItem ? $orderItem->qty : 0;
            $qtyText = "Qty: {$qty}";

            return [
                'itemId' => $orderItem->id,
                'productName' => $product ? $product->name : 'Unknown Product',
                'subtitle' => $subtitle,
                'rating' => 4.5,
                'qtyText' => $qtyText,
                'orderType' => $orderItem->order_type,
                'totalPrice' => '₹' . (float) $orderItem->total_price,
                'mrpPrice' => '₹' . ((float) $orderItem->price * $orderItem->qty),
                'image' => ($variant && $variant->image) ? asset('uploads/products/' . $variant->image) : null,
            ];
        });

        // ----------------------------------------------------------------------
        // 5. Bill Summary Formatting 
        // ----------------------------------------------------------------------
        $totalMrp = $order->items->sum(function ($item) {
            return (float) $item->price * $item->qty; // Extract Original MRP from order items
        });

        $totalActivePrice = (float) $order->subtotal; // As stored exactly in DB (e.g. 30,500)
        $totalItemDiscount = $totalMrp - $totalActivePrice; // MRP - ActivePrice (e.g. 37,000 - 30,500 = 6,500)

        $couponDiscountAmount = (float) $order->discount_amount; // Coupon Discount (e.g. 3050)
        $taxAmount = (float) $order->tax_amount;
        $deliveryCharges = (float) $order->delivery_charges;

        $totalSavings = $totalItemDiscount + $couponDiscountAmount; // Item Discount + Coupon Discount

        $itemCount = $order->items->count();

        $billSummary = [
            'subtotalLabel' => "Items Subtotal ({$itemCount} Items)",
            'subtotalValue' => '₹' . round($totalActivePrice, 2),

            // Added explicit keys to handle both item-level discount and coupon discount separately
            'discountValue' => $totalItemDiscount > 0 ? '₹' . round($totalItemDiscount, 2) : 'FREE',
            'bulkDiscountValue' => $totalItemDiscount > 0 ? '₹' . round($totalItemDiscount, 2) : 'FREE', // mapped item discount here
            'couponDiscountValue' => $couponDiscountAmount > 0 ? '₹' . round($couponDiscountAmount, 2) : 'FREE', // explicit coupon key

            'deliveryChargesValue' => $deliveryCharges > 0 ? '₹' . round($deliveryCharges, 2) : 'FREE',
            'taxesValue' => '₹' . round($taxAmount, 2),
            'totalSavingsValue' => $totalSavings > 0 ? '-₹' . round($totalSavings, 2) : '₹0',
            'totalPayableValue' => '₹' . round((float) $order->grand_total, 2),
            'initialPaymentValue' => '₹' . round((float) $order->initial_payment, 2),
            'pendingPaymentValue' => '₹' . round((float) $order->pending_payment, 2),
            'paymentType' => $order->payment_type,
            'orderType' => $order->order_type,
        ];

        // ----------------------------------------------------------------------
        // 6. Delivery Address Formatting
        // ----------------------------------------------------------------------
        $deliveryAddress = null;
        if ($order->address) {
            $deliveryAddress = [
                'tag' => $order->address->site_name ?? $order->address->address_title ?? 'Home',
                'addressLine' => $order->address->delivery_address ?? '',
                'latitude' => $order->address->latitude ?? null,
                'longitude' => $order->address->longitude ?? null,
            ];
        }

        // ----------------------------------------------------------------------
        // 7. Quantity Verification Status
        // ----------------------------------------------------------------------
        $verifications = DB::table('order_qty_verification')
            ->where('order_id', $order->id)
            ->where('user_id', $user->id)
            ->pluck('verification_status');

        $isQtyVerification = null;
        if ($verifications->isNotEmpty()) {
            if ($verifications->contains('Rejected')) {
                $isQtyVerification = 2;
            } elseif ($verifications->contains('Pending')) {
                $isQtyVerification = 1;
            } else {
                $isQtyVerification = 0;
            }
        }

        $statusMap = [
            1 => 'In-Review',
            2 => 'Confirmed',
            3 => 'Completed',
            4 => 'Out for Delivery',
            5 => 'Cancelled',
            6 => 'Rejected',
            7 => 'Shipped',
            8 => 'Customer Rejected',
            9 => 'Reject Approved',
        ];
        $currentStatusString = $statusMap[$order->status] ?? 'Unknown';

        return response()->json([
            'status' => true,
            'message' => 'Order details retrieved successfully.',
            'data' => [
                'orderId' => $order->id,
                'orderType' => $order->order_type,
                'currentStatus' => $currentStatusString,
                'isQtyVerification' => $isQtyVerification,
                'uiConfig' => $screenConfig,
                'timeline' => $timeline,
                'personnel' => [
                    'siteEngineer' => $siteEngineer,
                    'driver' => $driver
                ],
                'deliveryOtp' => $screenConfig['showOtp'] ? $order->delivery_otp : null,
                'items' => $formattedItems,
                'billSummary' => $billSummary,
                'deliveryAddress' => $deliveryAddress,
            ]
        ], 200);
    }

    /**
     * Get Rejection Reasons (Categorized)
     */
    public function getRejectReasons()
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $reasons = [
            [
                'category' => 'NirmaanNXT Fault',
                'noticeMessage' => 'Your return pickup is completely FREE. NirmaanNXT bears the return transportation cost.',
                'options' => [
                    'Wrong Product',
                    'Wrong Quantity',
                    'Damaged Material',
                    'Verified Quality Issue',
                    'Wrong Grade/Specification',
                    'Dispatch Error'
                ]
            ],
            [
                'category' => 'Customer/Site Fault',
                'noticeMessage' => 'Applicable return/transport charges must be paid by the customer',
                'options' => [
                    'Site not ready',
                    'Site access problem',
                    'Customer unavailable',
                    'Wrong address',
                    'Requirement changed',
                    'Customer refuses correctly supplied material',
                    'Other verified customer-side issue'
                ]
            ],
            [
                'category' => 'Need further verification',
                'noticeMessage' => "Applicable return/transport charges must be paid by the customer",
                'options' => []
            ]
        ];

        return response()->json([
            'status' => true,
            'message' => 'Rejection reasons retrieved successfully.',
            'data' => $reasons
        ], 200);
    }

    /**
     * Reject Order by Customer
     */
    /**
     * Reject Order by Customer
     */
    public function rejectOrderDelivery(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id',
            'category' => 'required|string|max:100',
            'reason' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('id', $request->orderId)->where('user_id', $user->id)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->status != 4) {
            return response()->json([
                'status' => false,
                'message' => 'Order cannot be rejected at this stage. It must be Out for Delivery.'
            ], 400);
        }

        $fullReason = $request->category;
        if (!empty($request->reason)) {
            $fullReason .= ' - ' . $request->reason;
        }

        $order->status = 8;
        $order->cancel_reason = $fullReason;
        $order->save();

        // 1. Rejection Request Confirmation Notification
        $detailsText = !empty($request->reason) ? " ({$request->reason})" : "";
        $rejectionNotificationMessage = "Delivery rejection request submitted for order {$order->order_number}. Category: {$request->category}{$detailsText}.";
        $this->sendAndSaveNotification(
            $user->id,
            $user->device_token,
            "Delivery Rejection Submitted",
            $rejectionNotificationMessage,
            'order_tracking'
        );

        // 2. Review & Refund Next-Step Notification
        $reviewNotificationMessage = "Your rejection request for order {$order->order_number} is under admin review. Any applicable refund or return charges will be updated once approved.";
        $this->sendAndSaveNotification(
            $user->id,
            $user->device_token,
            "Rejection Under Review",
            $reviewNotificationMessage,
            'order_tracking'
        );

        return response()->json([
            'status' => true,
            'message' => 'Order rejection request submitted successfully. Pending admin approval.'
        ], 200);
    }

    /**
     * Get Order Quantity (For Verification Screen)
     */
    public function getOrderQty(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::with(['items.product', 'items.variant'])
            ->where('id', $request->orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found.'
            ], 404);
        }

        $verifications = DB::table('order_qty_verification')
            ->where('order_id', $order->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy('item_id');

        $formattedItems = $order->items->map(function ($item) use ($verifications) {
            $variant = $item->variant;
            $product = $item->product;
            $unit = $variant ? $variant->unit : 'Units';

            $attributeText = '1 Unit';
            if ($variant && !empty($variant->attributes)) {
                $attributes = $variant->attributes;
                if (is_string($attributes)) {
                    $decoded = json_decode($attributes, true);
                    $attributes = json_last_error() === JSON_ERROR_NONE ? $decoded : $attributes;
                }
                $attributeText = is_array($attributes) ? implode(', ', $attributes) : $attributes;
            }

            $verification = $verifications->get($item->id);

            return [
                'itemId' => $item->id,
                'productId' => $item->product_id,
                'variantId' => $item->variant_id,
                'productName' => $product ? $product->name : 'Unknown Product',
                'subtitle' => $attributeText,
                'orderedQty' => (float) $item->qty,
                'deliveredQty' => $verification ? (float) $verification->delivered_qty : null,
                'unit' => $unit,
                'orderType' => $item->order_type,
                'verificationStatus' => $verification ? $verification->verification_status : null,
            ];
        });

        $itemCount = $order->items->count();

        // ----------------------------------------------------------------------
        // Bill Summary Calculations Alignment
        // ----------------------------------------------------------------------
        $totalMrp = $order->items->sum(function ($item) {
            return (float) $item->price * $item->qty;
        });
        $totalActivePrice = (float) $order->subtotal;
        $totalItemDiscount = $totalMrp - $totalActivePrice;
        $couponDiscountAmount = (float) $order->discount_amount;
        $taxAmount = (float) $order->tax_amount;
        $deliveryCharges = (float) $order->delivery_charges;

        $totalSavings = $totalItemDiscount + $couponDiscountAmount;

        if ($verifications->isEmpty()) {
            $overallStatus = null;
        } elseif ($verifications->contains('verification_status', 'Rejected')) {
            $overallStatus = 'Rejected';
        } elseif ($verifications->contains('verification_status', 'Pending')) {
            $overallStatus = 'Pending';
        } else {
            $overallStatus = 'Approved';
        }

        return response()->json([
            'status' => true,
            'message' => 'Order quantity details retrieved successfully.',
            'data' => [
                'orderId' => $order->id,
                'verificationStatus' => $overallStatus,
                'items' => $formattedItems,
                'billSummary' => [
                    'subtotalLabel' => "Items Subtotal ({$itemCount} Items)",
                    'subtotalAmount' => round($totalActivePrice, 2),
                    'bulkDiscountValue' => $couponDiscountAmount > 0 ? '-₹' . round($couponDiscountAmount, 2) : 'FREE',
                    'totalPayableValue' => round((float) $order->grand_total, 2),
                    'initialPaymentValue' => round((float) $order->initial_payment, 2),
                    'pendingPaymentValue' => round((float) $order->pending_payment, 2),
                    'totalSavingsValue' => $totalSavings > 0 ? '-₹' . round($totalSavings, 2) : '₹0',
                    'deliveryChargesValue' => $deliveryCharges > 0 ? '₹' . round($deliveryCharges, 2) : 'FREE',
                    'taxesValue' => '₹' . round($taxAmount, 2),
                    'paymentType' => $order->payment_type,
                ]
            ]
        ], 200);
    }

    /**
     * Verify and Submit Order Quantities
     */
    /**
     * Verify and Submit Order Quantities
     */
    public function verifyOrderQty(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id',
            'items' => 'required|array|min:1',
            'items.*.itemId' => 'required|integer|exists:order_items,id',
            'items.*.deliveredQty' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $order = Order::where('id', $request->orderId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$order) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
            }

            $alreadyVerified = DB::table('order_qty_verification')
                ->where('order_id', $order->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyVerified) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'This order has already been verified. Multiple submissions are not allowed.'], 409);
            }

            $order->load('items.variant');
            $verificationData = [];
            $now = Carbon::now();
            $allQuantitiesMatch = true;

            foreach ($request->items as $reqItem) {
                $orderItem = $order->items->where('id', $reqItem['itemId'])->first();

                if (!$orderItem) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'One or more items do not belong to this order.'], 422);
                }

                $variant = $orderItem->variant;
                $unit = $variant ? $variant->unit : 'Units';
                $orderedQty = (float) $orderItem->qty;
                $deliveredQty = (float) $reqItem['deliveredQty'];

                $itemStatus = ($orderedQty === $deliveredQty) ? 'Approved' : 'Pending';

                if ($itemStatus === 'Pending') {
                    $allQuantitiesMatch = false;
                }

                $verificationData[] = [
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'variant_id' => $orderItem->variant_id,
                    'ordered_qty' => $orderedQty,
                    'delivered_qty' => $deliveredQty,
                    'unit' => $unit,
                    'verification_status' => $itemStatus,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            $submittedItemIds = collect($request->items)->pluck('itemId')->map(fn($id) => (int) $id);

            if ($submittedItemIds->unique()->count() !== $submittedItemIds->count() || $submittedItemIds->count() !== $order->items->count()) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Submit each order item exactly once.'], 422);
            }

            DB::table('order_qty_verification')->insert($verificationData);

            if ($allQuantitiesMatch) {
                $order->save();
            }

            DB::commit();

            $verifiedItemCount = count($verificationData);
            $matchedItemCount = collect($verificationData)->where('verification_status', 'Approved')->count();
            $pendingItemCount = collect($verificationData)->where('verification_status', 'Pending')->count();

            // 1. Quantity Verification Receipt Notification
            $receiptMessage = "Quantity verification submitted for order {$order->order_number} ({$matchedItemCount}/{$verifiedItemCount} items matched).";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Quantity Verification Submitted",
                $receiptMessage,
                'order_tracking'
            );

            // 2. Discrepancy / Approval Status Notification
            if ($allQuantitiesMatch) {
                $statusMessage = "All {$verifiedItemCount} item(s) in order {$order->order_number} matched delivered quantities with no discrepancies.";
                $this->sendAndSaveNotification(
                    $user->id,
                    $user->device_token,
                    "Quantities Matched",
                    $statusMessage,
                    'order_tracking'
                );
            } else {
                $statusMessage = "{$pendingItemCount} item(s) in order {$order->order_number} have quantity differences and are pending admin review for bill adjustment.";
                $this->sendAndSaveNotification(
                    $user->id,
                    $user->device_token,
                    "Discrepancy Under Review",
                    $statusMessage,
                    'order_tracking'
                );
            }

            return response()->json([
                'status' => true,
                'message' => $allQuantitiesMatch
                    ? 'Quantities verified successfully. Order is now Completed.'
                    : 'Quantity verification submitted. Discrepancies are pending approval.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to submit verification.'], 500);
        }
    }

    /**
     * Get Quantity Verification Status and Details
     */
    public function getQtyVerificationStatus(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('id', $request->orderId)->where('user_id', $user->id)->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        $verifications = DB::table('order_qty_verification')
            ->join('products', 'order_qty_verification.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'order_qty_verification.variant_id', '=', 'product_variants.id')
            ->select(
                'order_qty_verification.*',
                'products.name as product_name',
                'product_variants.attributes'
            )
            ->where('order_qty_verification.order_id', $order->id)
            ->get();

        if ($verifications->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No quantity verification request found for this order.',
                'data' => null
            ], 200);
        }

        $overallStatus = 'Approved';
        $hasPending = false;
        $hasRejected = false;

        $formattedItems = $verifications->map(function ($v) use (&$hasPending, &$hasRejected) {
            if ($v->verification_status === 'Pending')
                $hasPending = true;
            if ($v->verification_status === 'Rejected')
                $hasRejected = true;

            $attributeText = '1 Unit';
            if (!empty($v->attributes)) {
                $attr = json_decode($v->attributes, true) ?? $v->attributes;
                $attributeText = is_array($attr) ? implode(', ', $attr) : $attr;
            }

            return [
                'itemId' => (int) $v->item_id,
                'productName' => $v->product_name,
                'subtitle' => $attributeText,
                'orderedQty' => (float) $v->ordered_qty,
                'deliveredQty' => (float) $v->delivered_qty,
                'unit' => $v->unit,
                'itemStatus' => $v->verification_status,
                'submittedAt' => Carbon::parse($v->created_at)->format('d M, Y h:i A')
            ];
        });

        if ($hasRejected) {
            $overallStatus = 'Rejected';
        } elseif ($hasPending) {
            $overallStatus = 'Pending';
        }

        $adminNote = null;
        if ($overallStatus === 'Rejected' && str_contains($order->cancel_reason, 'Qty Reject Note:')) {
            $adminNote = str_replace('Qty Reject Note: ', '', $order->cancel_reason);
        }

        return response()->json([
            'status' => true,
            'message' => 'Verification details retrieved successfully.',
            'data' => [
                'orderId' => $order->id,
                'orderNumber' => $order->order_number,
                'overallStatus' => $overallStatus,
                'adminNote' => $adminNote,
                'items' => $formattedItems
            ]
        ], 200);
    }

    /**
     * Get Pending Payment Details (For Payment Screen)
     * Fully robust against database overwrites via Dynamic Verification Recalculation
     */
    public function getPendingPaymentDetails(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::with('items')
            ->where('id', $request->orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        $verifications = DB::table('order_qty_verification')
            ->where('order_id', $order->id)
            ->where('verification_status', 'Approved')
            ->get()
            ->keyBy('item_id');

        $hasApprovedDiscrepancy = false;

        $originalActivePrice = 0;
        $newActivePrice = 0;
        $totalMrp = 0;

        foreach ($order->items as $item) {
            $verification = $verifications->get($item->id);

            // Explicitly map old vs new quantities to bypass DB overwrite issues
            $orderedQty = $verification ? (float) $verification->ordered_qty : (float) $item->qty;
            $deliveredQty = $verification ? (float) $verification->delivered_qty : (float) $item->qty;

            if ($verification && $orderedQty > $deliveredQty) {
                $hasApprovedDiscrepancy = true;
            }

            $totalMrp += (float) $item->price * $deliveredQty;
            $originalActivePrice += (float) $item->selling_price * $orderedQty;
            $newActivePrice += (float) $item->selling_price * $deliveredQty;
        }

        $newTax = $newActivePrice * 0.18;
        $newDelivery = 0;

        $originalTax = $originalActivePrice * 0.18;
        $originalDelivery = 0;

        $newCouponDiscount = 0;
        $originalCouponDiscount = 0;

        if ($order->is_coupon_applied && $order->coupon_id) {
            $coupon = DiscountCoupon::find($order->coupon_id);
            if ($coupon) {
                // Calculate New Coupon Deductions
                if (
                    ($coupon->coupon_type === 'Normal' && $newActivePrice >= $coupon->min_order_amount) ||
                    ($coupon->coupon_type === 'Bill Threshold' && $newActivePrice >= $coupon->threshold_amount)
                ) {
                    if ($coupon->discount_type === 'Percentage') {
                        $newCouponDiscount = ($newActivePrice * $coupon->discount_value) / 100;
                        if ($coupon->max_discount > 0 && $newCouponDiscount > $coupon->max_discount)
                            $newCouponDiscount = $coupon->max_discount;
                    } else {
                        $newCouponDiscount = $coupon->discount_value;
                    }
                }

                // Calculate Original Coupon Deductions
                if (
                    ($coupon->coupon_type === 'Normal' && $originalActivePrice >= $coupon->min_order_amount) ||
                    ($coupon->coupon_type === 'Bill Threshold' && $originalActivePrice >= $coupon->threshold_amount)
                ) {
                    if ($coupon->discount_type === 'Percentage') {
                        $originalCouponDiscount = ($originalActivePrice * $coupon->discount_value) / 100;
                        if ($coupon->max_discount > 0 && $originalCouponDiscount > $coupon->max_discount)
                            $originalCouponDiscount = $coupon->max_discount;
                    } else {
                        $originalCouponDiscount = $coupon->discount_value;
                    }
                }
            }
        }

        $newGrandTotal = $newActivePrice - $newCouponDiscount + $newTax + $newDelivery;
        $originalGrandTotal = $originalActivePrice - $originalCouponDiscount + $originalTax + $originalDelivery;

        $discrepancyAmount = $originalGrandTotal - $newGrandTotal;

        $infoMessage = null;
        $totalOrderAmount = (float) $order->grand_total;
        $pendingAmountToPay = (float) $order->pending_payment;
        $initialPaymentPaid = (float) $order->initial_payment;

        if ($hasApprovedDiscrepancy && $discrepancyAmount > 0) {
            $totalOrderAmount = $newGrandTotal;

            if ($order->payment_type === 'Full Payment') {
                $infoMessage = "You will receive a refund amount of ₹" . round($discrepancyAmount, 2) . " to your account within 3-4 Business days.";
                $pendingAmountToPay = 0;
            } else {
                $calculatedPending = $newGrandTotal - $initialPaymentPaid;

                if ($calculatedPending < 0) {
                    $refund = abs($calculatedPending);
                    $pendingAmountToPay = 0;
                    $infoMessage = "Your pending amount is settled. You will receive a refund amount of ₹" . round($refund, 2) . " to your account within 3-4 Business days.";
                } else {
                    $pendingAmountToPay = $calculatedPending;
                    $infoMessage = "Your pending amount has been adjusted and decreased by ₹" . round($discrepancyAmount, 2) . " due to unavailable quantity.";
                }
            }
        } elseif (!$hasApprovedDiscrepancy) {
            // Clean slate safety reset if no discrepancy
            $totalOrderAmount = $newGrandTotal;
        }

        $totalItemDiscount = $totalMrp - $newActivePrice;
        $totalSavings = $newCouponDiscount;
        $itemCount = $order->items->count();

        return response()->json([
            'status' => true,
            'message' => 'Payment details retrieved successfully.',
            'data' => [
                'orderId' => $order->id,
                'infoMessage' => $infoMessage,
                'billSummary' => [
                    'subtotalLabel' => "Items Subtotal ({$itemCount} Items)",
                    'subtotalValue' => '₹' . round($newActivePrice, 2),
                    'bulkDiscountValue' => $newCouponDiscount > 0 ? '-₹' . round($newCouponDiscount, 2) : 'FREE',
                    'deliveryChargesValue' => $newDelivery > 0 ? '₹' . round($newDelivery, 2) : 'FREE',
                    'taxesValue' => '₹' . round($newTax, 2),
                    'itemDiscountValue' => $totalItemDiscount > 0 ? '-₹' . round($totalItemDiscount, 2) : 'FREE',
                    'totalSavingsValue' => $totalSavings > 0 ? '-₹' . round($totalSavings, 2) : '₹0',
                    'totalPayableValue' => '₹' . round($totalOrderAmount, 2),
                ],
                'paymentBreakdown' => [
                    'paymentType' => $order->payment_type,
                    'initialPaymentPaid' => round($initialPaymentPaid, 2),
                    'pendingAmountToPay' => round($pendingAmountToPay, 2),
                    'totalOrderAmount' => round($totalOrderAmount, 2)
                ]
            ]
        ], 200);
    }

    /**
     * Mark Order as Completed
     */
    /**
     * Mark Order as Completed
     */
    public function completeOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::where('id', $request->orderId)->where('user_id', $user->id)->first();
        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        try {
            DB::beginTransaction();
            $order->status = 3;
            $order->save();
            DB::commit();

            // Format amounts cleanly
            $formattedGrandTotal = '₹' . number_format((float) $order->grand_total, 2);
            $formattedInitialPayment = '₹' . number_format((float) $order->initial_payment, 2);
            $formattedPendingPayment = '₹' . number_format((float) $order->pending_payment, 2);

            // 1. Delivery Confirmation Notification
            $deliveryNotificationMessage = "Your order {$order->order_number} has been delivered and marked as completed. Thank you for choosing NirmaanNXT!";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Order Delivered Successfully",
                $deliveryNotificationMessage,
                'order_tracking'
            );

            // 2. Payment Summary Notification
            $paymentNotificationMessage = ((float) $order->pending_payment > 0)
                ? "Order {$order->order_number} summary ({$order->payment_type}): Total {$formattedGrandTotal}, Paid {$formattedInitialPayment}, Pending balance {$formattedPendingPayment}."
                : "Order {$order->order_number} summary ({$order->payment_type}): Total {$formattedGrandTotal} is fully settled with ₹0.00 balance due.";

            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Order Payment Summary",
                $paymentNotificationMessage,
                'order_tracking'
            );

            return response()->json([
                'status' => true,
                'message' => 'Order Delivered Successfully.',
                'data' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'paymentType' => $order->payment_type,
                    'transactionId' => 'TIDN' . $order->id . time(),
                    'dateAndTime' => Carbon::now()->format('d M, Y, h:i A'),
                    'amountPaid' => (float) $order->grand_total
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to complete order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Completed Order Summary
     */
    public function getCompletedOrderSummary(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::with(['items.product', 'items.variant'])
            ->where('id', $request->orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order || $order->status != 3) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found or is not marked as completed.'
            ], 400);
        }

        $orderRating = \App\Models\OrderRating::where('order_id', $order->id)->where('user_id', $user->id)->first();
        $productRatings = \App\Models\ProductRating::where('order_id', $order->id)
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(function ($rating) {
                return $rating->product_id . '_' . $rating->variant_id;
            });

        $formattedItems = $order->items->map(function ($orderItem) use ($productRatings) {
            $variant = $orderItem->variant;
            $product = $orderItem->product;

            $attributeText = '1 Unit';
            if ($variant && !empty($variant->attributes)) {
                $attributeText = is_array($variant->attributes) ? implode(', ', $variant->attributes) : $variant->attributes;
            }

            $unit = $variant ? ($variant->unit ?: 'Units') : 'Units';
            $subtitle = "{$attributeText} per {$unit} • ₹" . (float) $orderItem->selling_price . " each";
            $qtyText = "Qty: {$orderItem->qty}";
            $ratingKey = $orderItem->product_id . '_' . $orderItem->variant_id;
            $productRating = $productRatings->get($ratingKey);

            return [
                'itemId' => $orderItem->id,
                'productId' => $orderItem->product_id,
                'variantId' => $orderItem->variant_id,
                'productName' => $product ? $product->name : 'Unknown Product',
                'subtitle' => $subtitle,
                'qtyText' => $qtyText,
                'totalPrice' => '₹' . (float) $orderItem->total_price,
                'mrpPrice' => '₹' . ((float) $orderItem->price * $orderItem->qty),
                'orderType' => $orderItem->order_type,
                'image' => ($variant && $variant->image) ? asset('uploads/products/' . $variant->image) : null,
                'isRated' => $productRating !== null,
                'userRating' => $productRating ? (int) $productRating->rating : null,
            ];
        });

        $transactionId = 'TIDN' . $order->id . strtotime($order->updated_at);

        return response()->json([
            'status' => true,
            'message' => 'Completed order summary retrieved successfully.',
            'data' => [
                'headerTitle' => 'Order Delivered Successfully',
                'headerSubtitle' => 'Explore more products to order now',
                'orderRating' => [
                    'isRated' => $orderRating !== null,
                    'rating' => $orderRating ? (int) $orderRating->rating : null,
                    'feedback' => $orderRating ? $orderRating->feedback : null,
                ],
                'orderInfo' => [
                    'orderId' => $order->id,
                    'orderNumber' => $order->order_number,
                    'transactionId' => $transactionId,
                    'dateAndTime' => Carbon::parse($order->updated_at)->format('d M, Y, h:i A'),
                    'amountPaid' => '₹' . (float) $order->grand_total,
                ],
                'invoiceUrl' => route('api.download_invoice', ['id' => $order->id]),
                'items' => $formattedItems,
            ],
        ], 200);
    }

    /**
     * Download Order Invoice
     */
    public function downloadInvoice($id)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $order = Order::with(['items.product', 'items.variant', 'address', 'coupon', 'user'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order || $order->status != 3) {
            return response()->json(['status' => false, 'message' => 'Invoice is only available for completed orders.'], 400);
        }

        $pdf = Pdf::loadView('api.invoices.order_invoice', compact('order'));
        return $pdf->download('Invoice_' . $order->order_number . '.pdf');
    }

    /**
     * Get Master List of Active Cancel Reasons
     */
    public function getCancelReasons(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $cancelReasons = \App\Models\CancelReason::where('status', 1)->where('trash', 0)->orderBy('id', 'asc')->get();
        $formattedReasons = $cancelReasons->map(function ($reason) {
            return [
                'id' => $reason->id,
                'reasonName' => $reason->name,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Cancel reasons retrieved successfully.',
            'data' => ['cancelReasons' => $formattedReasons]
        ], 200);
    }

    /**
     * Rate Order
     */
    public function rateOrder(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $order = Order::where('id', $request->orderId)->where('user_id', $user->id)->first();
        if (!$order || $order->status != 3) {
            return response()->json(['status' => false, 'message' => 'You can only rate completed orders.'], 400);
        }

        \App\Models\OrderRating::updateOrCreate(
            ['order_id' => $order->id, 'user_id' => $user->id],
            ['rating' => $request->rating, 'feedback' => $request->feedback, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        );

        return response()->json(['status' => true, 'message' => 'Order rating submitted successfully.'], 200);
    }

    /**
     * Rate Product Variant
     */
    public function rateProductVariant(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'orderId' => 'required|integer|exists:orders,id',
            'productId' => 'required|integer|exists:products,id',
            'variantId' => 'required|integer|exists:product_variants,id',
            'rating' => 'required|integer|min:1|max:5'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $order = Order::where('id', $request->orderId)->where('user_id', $user->id)->first();
        if (!$order || $order->status != 3) {
            return response()->json(['status' => false, 'message' => 'You can only rate products from completed orders.'], 400);
        }

        $itemExists = OrderItem::where('order_id', $order->id)
            ->where('product_id', $request->productId)
            ->where('variant_id', $request->variantId)
            ->exists();

        if (!$itemExists) {
            return response()->json(['status' => false, 'message' => 'This product variant was not found in the specified order.'], 400);
        }

        \App\Models\ProductRating::updateOrCreate(
            ['order_id' => $order->id, 'user_id' => $user->id, 'product_id' => $request->productId, 'variant_id' => $request->variantId],
            ['rating' => $request->rating, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]
        );

        return response()->json(['status' => true, 'message' => 'Product rating submitted successfully.'], 200);
    }
}