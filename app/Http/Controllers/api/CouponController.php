<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DiscountCoupon;
use App\Models\Cart;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{
    /**
     * Get list of active coupons categorized as eligible and locked based on Cart
     */
    public function getCoupons(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        // Validate cartId if provided
        $validator = Validator::make($request->all(), [
            'cartId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $cartId = $request->cartId;

        // 1. Calculate Cart Subtotal
        $cartItems = Cart::with('variant')
            ->where('user_id', $user->id)
            ->where('cart_group_id', $cartId)
            ->get();

        $subTotal = 0;

        foreach ($cartItems as $item) {
            if ($item->variant) {
                $activePrice = (float) ($item->variant->offer_price > 0 ? $item->variant->offer_price : $item->variant->price);
                $subTotal += ($activePrice * $item->qty);
            }
        }

        // 2. Fetch Active Coupons
        $today = Carbon::now()->format('Y-m-d');

        $coupons = DiscountCoupon::where('status', 1)
            ->where('trash', 0)
            ->where(function ($query) use ($today) {
                // Ensure the coupon is within the valid date range, or dates are null (never expires)
                $query->where(function ($q) use ($today) {
                    $q->where('start_date', '<=', $today)
                        ->where('end_date', '>=', $today);
                })->orWhere(function ($q) {
                    $q->whereNull('start_date')
                        ->whereNull('end_date');
                });
            })
            ->orderBy('position', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        if ($coupons->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No active coupons available right now.',
                'data' => [
                    'eligible' => [],
                    'locked' => []
                ]
            ], 200);
        }

        $eligibleCoupons = [];
        $lockedCoupons = [];

        // 3. Process and Categorize Coupons
        foreach ($coupons as $coupon) {

            // Determine the required threshold amount based on coupon type
            $requiredAmount = 0;
            if ($coupon->coupon_type === 'Normal') {
                $requiredAmount = (float) $coupon->min_order_amount;
            } elseif ($coupon->coupon_type === 'Bill Threshold') {
                $requiredAmount = (float) $coupon->threshold_amount;
            } else {
                $requiredAmount = (float) $coupon->min_order_amount; // Fallback
            }

            $isEligible = $subTotal >= $requiredAmount;

            $formattedCoupon = [
                'id' => $coupon->id,
                'title' => $coupon->title,
                'code' => strtoupper($coupon->code),
                'description' => $coupon->description,
                'logo' => $coupon->logo ? asset('uploads/coupons/' . $coupon->logo) : null,
                'discountType' => $coupon->discount_type, // 'Percentage' or 'Flat'
                'discountValue' => (float) $coupon->discount_value,
                'requiredAmount' => $requiredAmount,
                'maxDiscount' => (float) $coupon->max_discount, // Applicable if percentage-based
                'expiresOn' => $coupon->end_date ? Carbon::parse($coupon->end_date)->format('d M, Y') : null,
            ];

            if ($isEligible) {
                // Calculate Exact Savings
                $savings = 0;
                if ($coupon->discount_type === 'Percentage') {
                    $savings = ($subTotal * $coupon->discount_value) / 100;
                    if ($coupon->max_discount > 0 && $savings > $coupon->max_discount) {
                        $savings = $coupon->max_discount;
                    }
                } else {
                    $savings = $coupon->discount_value;
                }

                $formattedCoupon['savings'] = round($savings, 2);
                $formattedCoupon['message'] = "You will save ₹" . round($savings, 2) . " with this code.";

                $eligibleCoupons[] = $formattedCoupon;
            } else {
                // Calculate Deficit to Unlock
                $deficit = $requiredAmount - $subTotal;

                $formattedCoupon['deficit'] = round($deficit, 2);
                $formattedCoupon['message'] = "Add items worth ₹" . round($deficit, 2) . " more to unlock this coupon.";

                $lockedCoupons[] = $formattedCoupon;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Coupons retrieved successfully.',
            'data' => [
                'cartSubTotal' => round($subTotal, 2),
                'eligible' => $eligibleCoupons,
                'locked' => $lockedCoupons
            ]
        ], 200);
    }
}