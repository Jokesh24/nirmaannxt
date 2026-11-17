<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Wishlist;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Validator;

class WishlistController extends Controller
{
    /**
     * 1. Toggle Wishlist (Add or Remove based on explicit action)
     * Action: 1 = Add to Wishlist, 0 = Remove from Wishlist
     */
    public function toggleWishlist(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'variantId' => 'required|integer|exists:product_variants,id',
            'action' => 'required|in:0,1' // Enforce exactly 0 or 1
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $variantId = $request->variantId;
        $action = (int) $request->action;

        // Check if the item is already in the wishlist
        $wishlistItem = Wishlist::where('user_id', $user->id)
            ->where('variant_id', $variantId)
            ->first();

        if ($action === 1) {
            // Request to Add
            if (!$wishlistItem) {
                Wishlist::create([
                    'user_id' => $user->id,
                    'variant_id' => $variantId
                ]);
            }
            $message = 'Item added to wishlist.';
        } else {
            // Request to Remove (action === 0)
            if ($wishlistItem) {
                $wishlistItem->delete();
            }
            $message = 'Item removed from wishlist.';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'variantId' => (int) $variantId,
                'action' => $action
            ]
        ], 200);
    }

    /**
     * 2. Clear Entire Wishlist
     */
    public function clearWishlist(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        Wishlist::where('user_id', $user->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Wishlist cleared successfully.'
        ], 200);
    }

    /**
     * 3. Get Wishlist Data
     * Replicates the getProducts response strictly, filtering only by the user's wishlist variants.
     */
    public function getWishlist(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        // 1. Get array of variant IDs currently in the user's wishlist
        $wishlistVariantIds = Wishlist::where('user_id', $user->id)->pluck('variant_id')->toArray();

        if (empty($wishlistVariantIds)) {
            return response()->json([
                'status' => true,
                'message' => 'Your wishlist is empty.',
                'data' => [
                    'products' => []
                ]
            ], 200);
        }

        // 2. Fetch parent products that contain at least one of these variants
        $products = Product::with([
            'brand',
            'variants' => function ($q) use ($wishlistVariantIds) {
                // Load ONLY the variants that are in the wishlist and are active
                $q->whereIn('id', $wishlistVariantIds)
                    ->where('status', 1)
                    ->where('trash', 0);
            }
        ])
            ->whereHas('variants', function ($q) use ($wishlistVariantIds) {
                // Filter out products that don't have matching variants
                $q->whereIn('id', $wishlistVariantIds)
                    ->where('status', 1)
                    ->where('trash', 0);
            })
            ->where('status', 1)
            ->where('trash', 0)
            ->orderBy('id', 'desc')
            ->get();

        // 3. Map the collection exactly like the getProducts controller method
        $formattedProducts = $products->map(function ($product) {

            // Format Master Gallery URLs
            $galleryUrls = [];
            if (is_array($product->gallery)) {
                foreach ($product->gallery as $img) {
                    $galleryUrls[] = asset('uploads/products/gallery/' . $img);
                }
            }

            /*
                |--------------------------------------------------------------------------
                | Delivery Date
                |--------------------------------------------------------------------------
                */

            $deliveryDateString = null;

            if ($product->sla_days) {

                $deliveryDateString = \Carbon\Carbon::now()
                    ->addDays($product->sla_days)
                    ->format('d M, Y');
            }

            return [
                'id' => $product->id,
                'brandId' => $product->brand_id,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'categoryId' => $product->category_id,
                'subcategoryId' => $product->subcategory_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'shortDescription' => $product->short_description,
                'description' => $product->description,
                'isTrending' => (bool) $product->is_trending,
                'slaDays' => (int) $product->sla_days,
                'deliveryDate' => $deliveryDateString,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
                'gallery' => $galleryUrls,
                // Format associated variants (This will only include the wishlisted variants)
                'variants' => $product->variants->map(function ($variant) {

                    // Format Variant Gallery URLs
                    $variantGalleryUrls = [];
                    if (is_array($variant->gallery)) {
                        foreach ($variant->gallery as $vImg) {
                            $variantGalleryUrls[] = asset('uploads/products/gallery/' . $vImg);
                        }
                    }

                    return [
                        'id' => $variant->id,
                        'variantName' => $variant->variant_name,
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'offerPrice' => (float) $variant->offer_price,
                        'instantPrice' => (float) $variant->instant_price,
                        'instantOfferPrice' => (float) $variant->instant_offer_price,
                        'royaltyPercentage' => (float) $variant->royalty_percentage,
                        'qty' => (float) $variant->qty,
                        'unit' => $variant->unit,
                        'stock' => (int) $variant->stock,
                        'attributes' => $variant->attributes,
                        'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                        'gallery' => $variantGalleryUrls,
                    ];
                })->values()
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Wishlist retrieved successfully.',
            'data' => [
                'products' => $formattedProducts
            ]
        ], 200);
    }
}