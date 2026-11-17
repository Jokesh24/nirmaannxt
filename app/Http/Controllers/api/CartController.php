<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\ProductVariant;
use App\Models\DiscountCoupon;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * 1. Add to Cart
     */
    public function addToCart(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'variantId' => 'required|integer|exists:product_variants,id',
            'qty' => 'sometimes|numeric|min:1|max:10',
            'orderType' => 'nullable|in:1,2' // 1 = Standard, 2 = Instant
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $variant = ProductVariant::where('id', $request->variantId)->where('status', 1)->where('trash', 0)->first();

        if (!$variant) {
            return response()->json(['status' => false, 'message' => 'This product variant is currently unavailable.'], 404);
        }

        $qtyToAdd = $request->has('qty') ? $request->qty : 1;
        $orderType = $request->has('orderType') ? (int) $request->orderType : 1;

        // 1. Check if the user already has an active cart
        $existingCart = Cart::where('user_id', $user->id)->first();

        // 2. Check if this specific item + order_type is already in the cart
        $cartItem = Cart::where('user_id', $user->id)
            ->where('variant_id', $request->variantId)
            ->where('order_type', $orderType)
            ->first();

        if ($cartItem) {
            $cartItem->qty += $qtyToAdd;
            $cartItem->save();
            $masterCartId = $existingCart->cart_group_id;
            $message = 'Item quantity updated in cart.';
        } else {
            // Create item first to get its Auto-Increment ID
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'variant_id' => $request->variantId,
                'qty' => $qtyToAdd,
                'order_type' => $orderType
            ]);

            // If it's the first item, use its own ID as the master integer cartId. Otherwise, copy the existing one.
            $masterCartId = $existingCart ? $existingCart->cart_group_id : $cartItem->id;

            $cartItem->cart_group_id = $masterCartId;
            $cartItem->save();

            $message = 'Item added to cart successfully.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'cartId' => (int) $masterCartId,
                'cartItemId' => (int) $cartItem->id,
                'variantId' => (int) $cartItem->variant_id,
                'qty' => (float) $cartItem->qty,
                'orderType' => (int) $cartItem->order_type
            ]
        ], 200);
    }

    /**
     * 2. Update Cart Quantity
     */
    public function updateCartQty(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'cartItemId' => 'required|integer|exists:carts,id',
            'action' => 'required|string|in:increase,decrease'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $cartItem = Cart::with('variant')
            ->where('id', $request->cartItemId)
            ->where('user_id', $user->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found.'
            ], 404);
        }

        $variant = $cartItem->variant;

        if (!$variant) {
            return response()->json([
                'status' => false,
                'message' => 'Product variant no longer exists.'
            ], 404);
        }

        if ((int) $variant->status !== 1 || (int) $variant->trash !== 0) {
            return response()->json([
                'status' => false,
                'message' => 'This product variant is currently unavailable.'
            ], 422);
        }

        if ($request->action === 'increase') {
            $newQty = (float) $cartItem->qty + 1;
            $stock = (float) $variant->stock;

            // Maximum quantity allowed is 10
            if ($newQty > 10) {
                return response()->json([
                    'status' => false,
                    'message' => 'Maximum 10 items are allowed per product.',
                    'data' => [
                        'cartId' => $cartItem->id,
                        'qty' => (float) $cartItem->qty,
                        'maxQty' => 10
                    ]
                ], 422);
            }

            // Stock validation
            if ($stock > 0 && $newQty > $stock) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only ' . $stock . ' item(s) available in stock.',
                    'data' => [
                        'cartId' => $cartItem->id,
                        'qty' => (float) $cartItem->qty,
                        'stock' => $stock
                    ]
                ], 422);
            }

            $cartItem->qty = $newQty;
            $cartItem->save();

            $this->validateCartCoupon($user->id); // Check coupon eligibility after update

            return response()->json([
                'status' => true,
                'message' => 'Cart quantity increased successfully.',
                'data' => [
                    'cartId' => $cartItem->id,
                    'variantId' => $cartItem->variant_id,
                    'qty' => (float) $cartItem->qty
                ]
            ], 200);
        }

        if ($request->action === 'decrease') {
            $newQty = (float) $cartItem->qty - 1;

            if ($newQty <= 0) {
                $cartItem->delete();
                $this->validateCartCoupon($user->id); // Check coupon eligibility after removal
                return response()->json([
                    'status' => true,
                    'message' => 'Item removed from cart.',
                    'data' => [
                        'cartId' => (int) $request->cartId,
                        'qty' => 0
                    ]
                ], 200);
            }

            $cartItem->qty = $newQty;
            $cartItem->save();

            $this->validateCartCoupon($user->id); // Check coupon eligibility after update

            return response()->json([
                'status' => true,
                'message' => 'Cart quantity decreased successfully.',
                'data' => [
                    'cartId' => $cartItem->id,
                    'variantId' => $cartItem->variant_id,
                    'qty' => (float) $cartItem->qty
                ]
            ], 200);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid cart action.'
        ], 422);
    }

    /**
     * 3. Remove Item From Cart
     */
    public function removeFromCart(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'cartItemId' => 'required|integer|exists:carts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        Cart::where('id', $request->cartItemId)->where('user_id', $user->id)->delete();

        $this->validateCartCoupon($user->id); // Ensure coupon stays valid after item deletion

        return response()->json([
            'status' => true,
            'message' => 'Item removed from cart.'
        ], 200);
    }

    /**
     * 4. Clear Entire Cart
     */
    public function clearCart(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Cart cleared successfully.'
        ], 200);
    }

    /**
     * 5. Get Cart (With Pricing Math, Points Calculation & Coupon Details)
     */
    public function getCart(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $cartItems = Cart::with(['variant.product.brand', 'address', 'coupon'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Your cart is empty.',
                'data' => ['items' => [], 'summary' => null]
            ], 200);
        }

        $totalMrp = 0;
        $totalActivePrice = 0;
        $totalPoints = 0;

        $formattedItems = $cartItems->map(function ($item) use (&$totalMrp, &$totalActivePrice, &$totalPoints) {
            $variant = $item->variant;
            $product = $variant->product;
            $isInstant = ($item->order_type == 2);

            // Fetch dynamic pricing based on order_type
            $mrp = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_price : (float) $variant->price;
            $offerPrice = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_offer_price : (float) $variant->offer_price;
            $activePrice = $offerPrice > 0 ? $offerPrice : $mrp;

            $itemTotalMrp = $mrp * $item->qty;
            $itemActiveTotal = $activePrice * $item->qty;

            $royaltyPercent = (float) ($variant->royalty_percentage ?? 0);
            $itemPoints = $royaltyPercent > 0 ? ($itemActiveTotal * ($royaltyPercent / 100)) : 0;

            $totalMrp += $itemTotalMrp;
            $totalActivePrice += $itemActiveTotal;
            $totalPoints += $itemPoints;

            $variantQty = (float) $variant->qty > 0 ? (float) $variant->qty : 1;
            $pricePerUnit = (float) $mrp / $variantQty;
            $offerPricePerUnit = $offerPrice > 0 ? ($offerPrice / $variantQty) : null;

            return [
                'cartItemId' => $item->id,
                'variantId' => $variant->id,
                'productId' => $product->id,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'productName' => $product->name,
                'variantName' => $variant->variant_name,
                'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                'cartQty' => (float) $item->qty,
                'qty' => (float) $variant->qty,
                'unit' => $variant->unit,
                'orderType' => (int) $item->order_type, // 1 = Standard, 2 = Instant
                'mrpPrice' => (float) $mrp,
                'activePrice' => (float) $activePrice,
                'perUnitPrice' => round($pricePerUnit, 2),
                'perUnitOfferPrice' => $offerPricePerUnit !== null ? round($offerPricePerUnit, 2) : null,
                'itemTotal' => (float) $itemActiveTotal,
                'pointsToEarn' => (float) $itemPoints,
                'couponDetails' => $item->coupon ? $item->coupon : null
            ];
        });

        $totalItemDiscount = $totalMrp - $totalActivePrice;
        $totalGst = $totalActivePrice * 0.18;

        $couponDiscountAmount = 0;
        $appliedCoupon = null;
        $couponId = $cartItems->first()->coupon_id;

        if ($couponId) {
            $today = Carbon::now()->format('Y-m-d');
            $coupon = DiscountCoupon::where('id', $couponId)
                ->where('status', 1)->where('trash', 0)
                ->where('start_date', '<=', $today)->where('end_date', '>=', $today)
                ->first();

            if ($coupon) {
                $isEligible = false;
                if ($coupon->coupon_type === 'Normal' && $totalActivePrice >= $coupon->min_order_amount) {
                    $isEligible = true;
                } elseif ($coupon->coupon_type === 'Bill Threshold' && $totalActivePrice >= $coupon->threshold_amount) {
                    $isEligible = true;
                }

                if ($isEligible) {
                    if ($coupon->discount_type === 'Percentage') {
                        $couponDiscountAmount = ($totalActivePrice * $coupon->discount_value) / 100;
                        if ($coupon->max_discount > 0 && $couponDiscountAmount > $coupon->max_discount) {
                            $couponDiscountAmount = $coupon->max_discount;
                        }
                    } else {
                        $couponDiscountAmount = $coupon->discount_value;
                    }
                    $appliedCoupon = $coupon;
                }
            }
        }

        $deliveryCharges = 0;
        $totalSavings = $totalItemDiscount + $couponDiscountAmount;
        $grandTotal = $totalActivePrice - $couponDiscountAmount + $deliveryCharges + $totalGst;

        $totalQuantity = $cartItems->sum('qty');

        $address = null;
        $selectedAddress = null;
        $addressId = $cartItems->first()->address_id;

        if ($addressId) {
            $selectedAddress = $cartItems->first()->address;
        } else {
            $selectedAddress = \App\Models\Address::where('user_id', $user->id)->where('status', 1)->where('is_selected', 1)->first();
            if (!$selectedAddress) {
                $selectedAddress = \App\Models\Address::where('user_id', $user->id)->where('status', 1)->latest()->first();
            }
        }

        if ($selectedAddress && $selectedAddress->status == 1) {
            $addressParts = array_filter([$selectedAddress->delivery_address, $selectedAddress->delivery_landmark, $selectedAddress->delivery_pincode]);
            $address = [
                'addressId' => $selectedAddress->id,
                'title' => $selectedAddress->site_name ?? $selectedAddress->address_title ?? 'Delivery Address',
                'fullAddress' => implode(', ', $addressParts)
            ];
        }

        $masterCartId = $cartItems->first()->cart_group_id;

        return response()->json([
            'status' => true,
            'message' => 'Cart retrieved successfully.',
            'data' => [
                'cartId' => $masterCartId,
                'address' => $address,
                'items' => $formattedItems,
                'summary' => [
                    'totalMrp' => round((float) $totalMrp, 2),
                    'totalItemDiscount' => round((float) $totalItemDiscount, 2),
                    'couponDiscountAmount' => round((float) $couponDiscountAmount, 2),
                    'totalSavings' => round((float) $totalSavings, 2),
                    'subTotal' => round((float) $totalActivePrice, 2),
                    'estimatedGst' => round((float) $totalGst, 2),
                    'deliveryCharges' => round((float) $deliveryCharges, 2),
                    'grandTotal' => round((float) $grandTotal, 2),
                    'totalPointsEarn' => round((float) $totalPoints, 2),
                    'totalItemsCount' => $cartItems->count(),
                    'qty' => (float) $totalQuantity,
                    'unit' => 'Units'
                ]
            ]
        ], 200);
    }

    /**
     * 6. Home (Formatted for UI Mockup)
     */
    public function home(Request $request)
    {
        $user = auth()->user();

        $pointsRecord = DB::table('user_reward_points')
            ->where('user_id', $user->id)
            ->select('total_earned')
            ->first();

        $totalEarned = $pointsRecord ? (float) $pointsRecord->total_earned : 0;
        $cartCount = \App\Models\Cart::where('user_id', $user->id)->count();
        $wishlistCount = \App\Models\Wishlist::where('user_id', $user->id)->count();

        return response()->json([
            'status' => true,
            'message' => 'Home data retrieved successfully.',
            'data' => [
                'total_earned' => $totalEarned,
                'cart_count' => $cartCount,
                'wishlist_count' => $wishlistCount
            ]
        ], 200);
    }

    /**
     * Update Address ID for Cart
     */
    public function updateCartAddress(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'addressId' => 'required|integer|exists:addresses,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $address = \App\Models\Address::where('id', $request->addressId)
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found or unauthorized.'
            ], 404);
        }

        $updatedCount = Cart::where('user_id', $user->id)
            ->update(['address_id' => $address->id]);

        return response()->json([
            'status' => true,
            'message' => 'Cart address updated successfully.',
            'data' => [
                'addressId' => (int) $address->id,
                'updatedCartItems' => $updatedCount
            ]
        ], 200);
    }

    /**
     * Helper Method: Validate the currently applied coupon against new cart totals
     */
    private function validateCartCoupon($userId)
    {
        $cartItems = Cart::with('variant')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return;
        }

        $couponId = $cartItems->first()->coupon_id;

        if (!$couponId) {
            return; // No coupon is currently applied
        }

        $totalActivePrice = 0;

        foreach ($cartItems as $item) {
            $variant = $item->variant;
            if ($variant) {
                $isInstant = ($item->order_type == 2);
                $mrp = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_price : (float) $variant->price;
                $offerPrice = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_offer_price : (float) $variant->offer_price;

                $activePrice = $offerPrice > 0 ? $offerPrice : $mrp;
                $totalActivePrice += $activePrice * $item->qty;
            }
        }

        $today = \Carbon\Carbon::now()->format('Y-m-d');

        $coupon = \App\Models\DiscountCoupon::where('id', $couponId)
            ->where('status', 1)
            ->where('trash', 0)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        $isEligible = false;

        if ($coupon) {
            if ($coupon->coupon_type === 'Normal' && $totalActivePrice >= $coupon->min_order_amount) {
                $isEligible = true;
            } elseif ($coupon->coupon_type === 'Bill Threshold' && $totalActivePrice >= $coupon->threshold_amount) {
                $isEligible = true;
            }
        }

        // If it's no longer eligible, nullify the coupon for all of the user's cart items
        if (!$isEligible) {
            Cart::where('user_id', $userId)->update(['coupon_id' => null]);
        }
    }

    /**
     * Set Coupon for the entire Cart
     */
    public function setCoupon(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'couponId' => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $hasItems = Cart::where('user_id', $user->id)->exists();

        if (!$hasItems) {
            return response()->json([
                'status' => false,
                'message' => 'Your cart is empty. Add items before applying a coupon.'
            ], 404);
        }

        // Convert 0 or null to null to remove the coupon
        $couponId = $request->input('couponId');

        if ($couponId == 0) {
            $couponId = null;
        }

        // Validate coupon existence only when applying a coupon
        if ($couponId !== null) {
            $couponExists = \DB::table('discount_coupons')
                ->where('id', $couponId)
                ->exists();

            if (!$couponExists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid coupon ID.'
                ], 422);
            }
        }

        Cart::where('user_id', $user->id)->update([
            'coupon_id' => $couponId
        ]);

        return response()->json([
            'status' => true,
            'message' => $couponId === null
                ? 'Coupon removed successfully.'
                : 'Coupon applied successfully.',
            'data' => [
                'couponId' => $couponId ?? 0
            ]
        ], 200);
    }

    /**
     * Get Payment Info (Comprehensive breakdown)
     */
    public function getPaymentInfo(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'cartId' => 'required|integer|exists:carts,cart_group_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $cartItems = Cart::with(['variant'])->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Your cart is empty.'], 400);
        }

        $totalMrp = 0;
        $totalActivePrice = 0;
        $taxAmount = 0;
        $deliveryCharges = 0;
        $couponDiscountAmount = 0;
        $couponId = $cartItems->first()->coupon_id;

        foreach ($cartItems as $item) {
            $variant = $item->variant;
            $isInstant = ($item->order_type == 2);

            $mrp = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_price : (float) $variant->price;
            $offerPrice = $isInstant && $variant->instant_price > 0 ? (float) $variant->instant_offer_price : (float) $variant->offer_price;
            $activePrice = $offerPrice > 0 ? $offerPrice : $mrp;

            $totalMrp += ($mrp * $item->qty);
            $totalActivePrice += ($activePrice * $item->qty);
        }

        $totalItemDiscount = $totalMrp - $totalActivePrice;
        $taxAmount = $totalActivePrice * 0.18;
        $deliveryCharges = 0;

        if ($couponId) {
            $today = Carbon::now()->format('Y-m-d');
            $coupon = DiscountCoupon::where('id', $couponId)->where('status', 1)->where('trash', 0)->where('start_date', '<=', $today)->where('end_date', '>=', $today)->first();

            if ($coupon) {
                $isEligible = false;
                if ($coupon->coupon_type === 'Normal' && $totalActivePrice >= $coupon->min_order_amount) {
                    $isEligible = true;
                } elseif ($coupon->coupon_type === 'Bill Threshold' && $totalActivePrice >= $coupon->threshold_amount) {
                    $isEligible = true;
                }

                if ($isEligible) {
                    if ($coupon->discount_type === 'Percentage') {
                        $couponDiscountAmount = ($totalActivePrice * $coupon->discount_value) / 100;
                        if ($coupon->max_discount > 0 && $couponDiscountAmount > $coupon->max_discount) {
                            $couponDiscountAmount = $coupon->max_discount;
                        }
                    } else {
                        $couponDiscountAmount = $coupon->discount_value;
                    }
                }
            }
        }

        $totalSavings = $totalItemDiscount + $couponDiscountAmount;
        $grandTotal = $totalActivePrice - $couponDiscountAmount + $deliveryCharges + $taxAmount;

        $initialPaymentPayNow = ($grandTotal >= 10000) ? 10000 : ($grandTotal * 0.20);
        $initialPaymentPending = $grandTotal - $initialPaymentPayNow;

        return response()->json([
            'status' => true,
            'message' => 'Payment info retrieved successfully.',
            'data' => [
                'payment_options' => [
                    [
                        'id' => 'initial_payment',
                        'title' => 'Initial Payment',
                        'description' => 'Pay advance now and the remaining balance amount at delivery',
                        'amount_to_pay_now' => round($initialPaymentPayNow, 2),
                    ],
                    [
                        'id' => 'full_payment',
                        'title' => 'Full Payment',
                        'description' => 'Pay the entire amount upfront—no payment is required at delivery',
                        'amount_to_pay_now' => round($grandTotal, 2),
                    ]
                ],
                'payment_summary' => [
                    'total_mrp' => round($totalMrp, 2),
                    'subtotal' => round($totalActivePrice, 2),
                    'tax_amount' => round($taxAmount, 2),
                    'delivery_charges' => round($deliveryCharges, 2),
                    'coupon_discount' => round($couponDiscountAmount, 2),
                    'total_savings' => round($totalSavings, 2),
                    'grandTotal' => round($grandTotal, 2),
                    'initialPayment' => round($initialPaymentPayNow, 2),
                    'pending' => round($initialPaymentPending, 2)
                ]
            ]
        ], 200);
    }
}