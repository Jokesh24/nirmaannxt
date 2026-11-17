<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\BulkCart;
use App\Models\BulkQuoteRequest;
use App\Models\BulkQuoteRequestItem;
use App\Models\BulkQuoteRequestItemAcceptance;
use App\Models\BulkQuoteRequestVendor;
use App\Models\BulkQuoteRequestVendorItem;
use App\Models\BulkQuoteOrder;
use App\Models\RecentSearch;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Traits\PushNotificationTrait; // Imported the trait


class BulkQuoteController extends Controller
{

    use PushNotificationTrait; // Used the trait

    protected $bulkQuantityOptions = [20, 50, 100, 150, 200];

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

    public function getBulkProducts(Request $request)
    {
        // (Unchanged from original)
        $keyword = $request->input('keyword');

        $brandIds = $request->input('brandId');
        $categoryIds = $request->input('categoryId');
        $subCategoryIds = $request->input('subCategoryId');
        $priceRange = $request->input('priceRange');
        $colors = $request->input('colors');
        $availability = $request->input('availability');
        $rating = $request->input('rating');
        $sortBy = $request->input('sortBy');

        $query = Product::with([
            'brand',
            'category',
            'subcategory',
            'variants' => function ($q) {
                $q->where('status', 1)->where('trash', 0)
                    ->whereHas('vendorBulkProducts', function ($vq) {
                        $vq->where('status', 1)->where('trash', 0);
                    });
            },
            'variants.vendorBulkProducts' => function ($vq) {
                $vq->where('status', 1)->where('trash', 0);
            }
        ])
            ->where('status', 1)
            ->where('trash', 0)
            ->whereHas('variants', function ($q) {
                $q->where('status', 1)->where('trash', 0)
                    ->whereHas('vendorBulkProducts', function ($vq) {
                        $vq->where('status', 1)->where('trash', 0);
                    });
            });

        if (!empty($keyword)) {
            $keyword = trim(preg_replace('/\s+/', ' ', $keyword));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%{$keyword}%")
                    ->orWhere('sku', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('brand', function ($brandQ) use ($keyword) {
                        $brandQ->where('brand_name', 'LIKE', "%{$keyword}%");
                    })->orWhereHas('subcategory', function ($subQ) use ($keyword) {
                        $subQ->where('name', 'LIKE', "%{$keyword}%");
                    })->orWhereHas('category', function ($catQ) use ($keyword) {
                        $catQ->where('name', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        if (!empty($brandIds)) {
            $brandsArr = is_array($brandIds) ? $brandIds : explode(',', $brandIds);
            $query->whereIn('brand_id', $brandsArr);
        }

        if (!empty($categoryIds)) {
            $catArr = is_array($categoryIds) ? $categoryIds : explode(',', $categoryIds);
            $query->whereIn('category_id', $catArr);
        }

        if (!empty($subCategoryIds)) {
            $subCatArr = is_array($subCategoryIds) ? $subCategoryIds : explode(',', $subCategoryIds);
            $query->whereIn('subcategory_id', $subCatArr);
        }

        if (!empty($priceRange)) {
            $parts = explode('-', $priceRange);
            if (count($parts) === 2) {
                $minBulkQty = min($this->bulkQuantityOptions);
                $maxBulkQty = max($this->bulkQuantityOptions);
                $lo = (float) $parts[0];
                $hi = (float) $parts[1];
                $query->whereHas('variants', function ($vq) use ($lo, $hi, $minBulkQty, $maxBulkQty) {
                    $vq->whereRaw(
                        '(COALESCE(NULLIF(offer_price, 0), price) * ?) <= ? AND (COALESCE(NULLIF(offer_price, 0), price) * ?) >= ?',
                        [$minBulkQty, $hi, $maxBulkQty, $lo]
                    );
                });
            }
        }

        if (!empty($colors)) {
            $colorArr = is_array($colors) ? $colors : explode(',', $colors);
            $query->whereHas('variants', function ($vq) use ($colorArr) {
                $vq->where(function ($colorQ) use ($colorArr) {
                    foreach ($colorArr as $color) {
                        $colorQ->orWhere('attributes', 'LIKE', '%"Color":"' . trim($color) . '"%');
                    }
                });
            });
        }

        if (!empty($availability) && in_array('instant_delivery', (array) $availability)) {
            $query->where(function ($q) {
                $q->where('instant_price', '>', 0)
                    ->orWhereHas('variants', function ($vq) {
                        $vq->where('instant_price', '>', 0);
                    });
            });
        }

        $products = $query->orderBy('id', 'desc')->get();
        $user = auth('api')->user() ?? auth()->user();

        $cartData = collect();
        if ($user) {
            $cartData = BulkCart::where('user_id', $user->id)
                ->get(['variant_id', 'qty', 'order_qty'])
                ->keyBy('variant_id');
        }

        $productRatings = \App\Models\ProductRating::whereIn('product_id', $products->pluck('id'))
            ->selectRaw('product_id, AVG(rating) as avg_rating')
            ->groupBy('product_id')
            ->pluck('avg_rating', 'product_id');

        $formattedProducts = $products->map(function ($product) use ($cartData, $rating, $productRatings, $priceRange) {
            $deliveryDateString = null;
            if ($product->sla_days) {
                $deliveryDateString = Carbon::now()->addDays($product->sla_days)->format('d M, Y');
            }

            $tag = $product->subcategory ? $product->subcategory->name : 'Standard Grade';
            $calculatedRating = isset($productRatings[$product->id]) ? round((float) $productRatings[$product->id], 1) : 0;

            if (!empty($rating) && $calculatedRating < (float) $rating) {
                return null;
            }

            $bulkVariants = $product->variants->filter(function ($variant) use ($priceRange) {
                if ((int) $variant->status !== 1 || (int) $variant->trash !== 0 || $variant->vendorBulkProducts->isEmpty()) {
                    return false;
                }

                if (!empty($priceRange)) {
                    $parts = explode('-', $priceRange);
                    if (count($parts) === 2) {
                        $minBulkQty = min($this->bulkQuantityOptions);
                        $maxBulkQty = max($this->bulkQuantityOptions);
                        $lo = (float) $parts[0];
                        $hi = (float) $parts[1];
                        $activePrice = (float) ($variant->offer_price > 0 ? $variant->offer_price : $variant->price);

                        $lowestTierTotal = $activePrice * $minBulkQty;
                        $highestTierTotal = $activePrice * $maxBulkQty;

                        if ($lowestTierTotal > $hi || $highestTierTotal < $lo) {
                            return false;
                        }
                    }
                }
                return true;
            });

            $variants = $bulkVariants->map(function ($variant) use ($cartData) {
                $price = (float) $variant->price;
                $offerPrice = (float) $variant->offer_price;
                $activePrice = $offerPrice > 0 ? $offerPrice : $price;

                $discountPercent = 0;
                if ($price > 0 && $offerPrice > 0 && $price > $offerPrice) {
                    $discountPercent = round((($price - $offerPrice) / $price) * 100);
                }

                $cartItem = $cartData->get($variant->id);
                $dbQty = $cartItem ? (int) $cartItem->qty : 0;
                $orderQty = $cartItem ? (int) $cartItem->order_qty : 0;
                $selectedBulkOption = ($dbQty > 0 && $orderQty > 0) ? (int) floor($dbQty / $orderQty) : 0;

                $bulkQuantityOptions = $this->buildBulkQuantityOptions($activePrice, $price);

                return [
                    'id' => $variant->id,
                    'variantName' => $variant->variant_name,
                    'sku' => $variant->sku,
                    'price' => $price,
                    'offerPrice' => $offerPrice,
                    'activePrice' => $activePrice,
                    'discountPercentage' => $discountPercent,
                    'unit' => $variant->unit,
                    'bulkQuantityOptions' => $bulkQuantityOptions,
                    'cartQty' => $selectedBulkOption,
                    'orderQty' => $orderQty,
                    'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                ];
            })->values();

            if ($variants->isEmpty()) {
                return null;
            }

            $minVariantPrice = $variants->min('activePrice');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
                'tag' => $tag,
                'rating' => $calculatedRating,
                'deliveryDate' => $deliveryDateString,
                'minVariantPrice' => $minVariantPrice,
                'variants' => $variants
            ];
        })->filter();

        if ($sortBy === 'price_asc') {
            $formattedProducts = $formattedProducts->sortBy('minVariantPrice');
        } elseif ($sortBy === 'price_desc') {
            $formattedProducts = $formattedProducts->sortByDesc('minVariantPrice');
        } elseif ($sortBy === 'popularity') {
            $formattedProducts = $formattedProducts->sortByDesc('rating');
        } else {
            $formattedProducts = $formattedProducts->sortByDesc('id');
        }

        $finalProducts = $formattedProducts->map(function ($item) {
            unset($item['minVariantPrice']);
            return $item;
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Bulk products retrieved successfully.',
            'data' => [
                'itemsFound' => $finalProducts->count(),
                'products' => $finalProducts
            ]
        ], 200);
    }

    protected function buildBulkQuantityOptions(float $activePrice, float $basePrice): array
    {
        return collect($this->bulkQuantityOptions)
            ->map(function ($qty) use ($activePrice, $basePrice) {
                $qty = (int) $qty;
                return [
                    'qty' => $qty,
                    'price' => round($activePrice * $qty, 2),
                    'basePrice' => round($basePrice * $qty, 2),
                ];
            })
            ->values()
            ->all();
    }

    public function searchBulkProducts(Request $request)
    {
        // (Unchanged from original)
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|min:2|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $keyword = trim(preg_replace('/\s+/', ' ', $request->keyword));
        $userId = Auth::guard('api')->id() ?? Auth::id();
        $user = auth('api')->user() ?? auth()->user();

        if ($userId) {
            RecentSearch::updateOrCreate(
                ['user_id' => $userId, 'keyword' => $keyword],
                ['updated_at' => now()]
            );
        }

        $words = explode(' ', $keyword);

        $query = Product::with([
            'brand',
            'category',
            'subcategory',
            'variants' => function ($q) {
                $q->where('status', 1)->where('trash', 0)
                    ->whereHas('vendorBulkProducts', function ($vq) {
                        $vq->where('status', 1)->where('trash', 0);
                    });
            },
            'variants.vendorBulkProducts' => function ($vq) {
                $vq->where('status', 1)->where('trash', 0);
            }
        ])
            ->where('status', 1)
            ->where('trash', 0)
            ->whereHas('variants', function ($q) {
                $q->where('status', 1)->where('trash', 0)
                    ->whereHas('vendorBulkProducts', function ($vq) {
                        $vq->where('status', 1)->where('trash', 0);
                    });
            });

        $query->where(function ($q) use ($words, $keyword) {
            $q->where('name', 'LIKE', "%{$keyword}%")
                ->orWhere('sku', 'LIKE', "%{$keyword}%");

            $q->orWhereHas('brand', function ($brandQ) use ($keyword) {
                $brandQ->where('brand_name', 'LIKE', "%{$keyword}%");
            })->orWhereHas('subcategory', function ($subQ) use ($keyword) {
                $subQ->where('name', 'LIKE', "%{$keyword}%");
            })->orWhereHas('category', function ($catQ) use ($keyword) {
                $catQ->where('name', 'LIKE', "%{$keyword}%");
            });

            foreach ($words as $word) {
                if (strlen($word) >= 3) {
                    $q->orWhere('name', 'LIKE', "%{$word}%")
                        ->orWhere('short_description', 'LIKE', "%{$word}%");

                    $fuzzyPattern = '%' . implode('%', str_split($word)) . '%';
                    $q->orWhere('name', 'LIKE', $fuzzyPattern);
                }
            }
        });

        $products = $query->orderByRaw("
            CASE 
                WHEN name LIKE ? THEN 1
                WHEN sku LIKE ? THEN 2
                WHEN name LIKE ? THEN 3
                ELSE 4
            END
        ", ["{$keyword}%", "{$keyword}%", "%{$keyword}%"])
            ->orderBy('id', 'desc')
            ->get();

        $cartData = collect();
        if ($user) {
            $cartData = BulkCart::where('user_id', $user->id)
                ->get(['variant_id', 'qty', 'order_qty'])
                ->keyBy('variant_id');
        }

        $formattedProducts = $products->map(function ($product) use ($cartData) {
            $deliveryDateString = null;
            if ($product->sla_days) {
                $deliveryDateString = Carbon::now()->addDays($product->sla_days)->format('d M, Y');
            }

            $tag = $product->subcategory ? $product->subcategory->name : 'Standard Grade';

            $bulkVariants = $product->variants->filter(function ($variant) {
                return (int) $variant->status === 1
                    && (int) $variant->trash === 0
                    && $variant->vendorBulkProducts->isNotEmpty();
            });

            $variants = $bulkVariants->map(function ($variant) use ($cartData) {
                $price = (float) $variant->price;
                $offerPrice = (float) $variant->offer_price;
                $activePrice = $offerPrice > 0 ? $offerPrice : $price;

                $discountPercent = 0;
                if ($price > 0 && $offerPrice > 0 && $price > $offerPrice) {
                    $discountPercent = round((($price - $offerPrice) / $price) * 100);
                }

                $cartItem = $cartData->get($variant->id);
                $dbQty = $cartItem ? (int) $cartItem->qty : 0;
                $orderQty = $cartItem ? (int) $cartItem->order_qty : 0;

                $selectedBulkOption = ($dbQty > 0 && $orderQty > 0) ? (int) floor($dbQty / $orderQty) : 0;

                return [
                    'id' => $variant->id,
                    'variantName' => $variant->variant_name,
                    'sku' => $variant->sku,
                    'price' => $price,
                    'offerPrice' => $offerPrice,
                    'activePrice' => $activePrice,
                    'discountPercentage' => $discountPercent,
                    'unit' => $variant->unit,
                    'bulkQuantityOptions' => $this->bulkQuantityOptions,
                    'cartQty' => $selectedBulkOption,
                    'orderQty' => $orderQty,
                    'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                ];
            })->values();

            if ($variants->isEmpty()) {
                return null;
            }

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
                'tag' => $tag,
                'rating' => 4.5,
                'deliveryDate' => $deliveryDateString,
                'variants' => $variants
            ];
        })->filter()->values();

        $relatedBrands = $products->pluck('brand')->filter()->unique('id')->values()->map(function ($brand) {
            return [
                'brandId' => $brand->id,
                'brandName' => $brand->brand_name,
                'brandLogo' => $brand->logo ? asset('uploads/brands/' . $brand->logo) : null,
            ];
        });

        $relatedCategories = $products->pluck('category')->filter()->unique('id')->values()->map(function ($category) {
            return [
                'categoryId' => $category->id,
                'categoryName' => $category->name,
                'categoryImage' => $category->image ? asset('uploads/category/' . $category->image) : null,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => $formattedProducts->isEmpty() ? 'No products found.' : 'Search results retrieved successfully.',
            'data' => [
                'products' => $formattedProducts,
                'relatedBrands' => $relatedBrands,
                'relatedCategories' => $relatedCategories
            ]
        ], 200);
    }

    public function addToBulkCart(Request $request)
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'variantId' => 'required|integer|exists:product_variants,id',
            'qty' => 'required|integer|min:0',
            'cartId' => 'nullable|integer|exists:bulk_carts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $variantId = (int) $request->variantId;
        $bulkQty = (int) $request->qty;

        if ($bulkQty === 0) {
            $deleteQuery = BulkCart::where('user_id', $user->id)
                ->where('variant_id', $variantId);

            if ($request->filled('cartId')) {
                $deleteQuery->where('id', (int) $request->cartId);
            }

            $deleteQuery->delete();

            return response()->json(['status' => true, 'message' => 'Item removed from bulk cart.'], 200);
        }

        $isBulkEligible = ProductVariant::where('id', $variantId)
            ->whereHas('vendorBulkProducts', function ($q) {
                $q->where('status', 1)->where('trash', 0);
            })->exists();

        if (!$isBulkEligible) {
            return response()->json(['status' => false, 'message' => 'This product is not eligible for bulk ordering.'], 400);
        }

        // Match by (user_id, variant_id, bulk_qty) so different pack options coexist as separate rows
        $cartItem = BulkCart::where('user_id', $user->id)
            ->where('variant_id', $variantId)
            ->where('bulk_qty', $bulkQty)
            ->first();

        if ($cartItem) {
            $cartItem->order_qty = (int) $cartItem->order_qty + 1;
            $cartItem->qty = $cartItem->order_qty * $bulkQty;
            $cartItem->save();
        } else {
            $cartItem = BulkCart::create([
                'user_id' => $user->id,
                'variant_id' => $variantId,
                'bulk_qty' => $bulkQty,
                'qty' => $bulkQty,
                'order_qty' => 1,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Bulk cart updated successfully.',
            'data' => [
                'cartId' => $cartItem->id,
                'variantId' => $cartItem->variant_id,
                'bulkQty' => (int) $cartItem->bulk_qty,
                'qty' => (int) $cartItem->qty,
                'orderQty' => (int) $cartItem->order_qty,
            ]
        ], 200);
    }

    public function updateBulkCartQty(Request $request)
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized or invalid account status.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'variantId' => 'required|integer|exists:product_variants,id',
            'action' => 'required|string|in:increase,decrease',
            'bulkQty' => 'required|integer|min:1',
            'cartId' => 'nullable|integer|exists:bulk_carts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $variantId = (int) $request->variantId;
        $action = $request->action;
        $bulkQty = (int) $request->bulkQty;

        $isBulkEligible = ProductVariant::where('id', $variantId)
            ->whereHas('vendorBulkProducts', function ($q) {
                $q->where('status', 1)->where('trash', 0);
            })->exists();

        if (!$isBulkEligible) {
            return response()->json(['status' => false, 'message' => 'This product is not eligible for bulk ordering.'], 400);
        }

        // Scope strictly to the matching pack tier (bulk_qty) or explicit cartId
        $cartQuery = BulkCart::where('user_id', $user->id)
            ->where('variant_id', $variantId);

        if ($request->filled('cartId')) {
            $cartQuery->where('id', (int) $request->cartId);
        } else {
            $cartQuery->where('bulk_qty', $bulkQty);
        }

        $cartItem = $cartQuery->first();
        $currentOrderQty = $cartItem ? (int) $cartItem->order_qty : 0;

        $newOrderQty = ($action === 'increase') ? ($currentOrderQty + 1) : ($currentOrderQty - 1);

        if ($newOrderQty <= 0) {
            if ($cartItem) {
                $cartItem->delete();
            }
            return response()->json([
                'status' => true,
                'message' => 'Item removed from bulk cart.',
                'data' => [
                    'variantId' => $variantId,
                    'qty' => 0,
                    'orderQty' => 0,
                    'bulkQty' => $bulkQty
                ]
            ], 200);
        }

        $newTotalQty = $newOrderQty * $bulkQty;

        if ($cartItem) {
            $cartItem->bulk_qty = $bulkQty;
            $cartItem->qty = $newTotalQty;
            $cartItem->order_qty = $newOrderQty;
            $cartItem->save();
        } else {
            $cartItem = BulkCart::create([
                'user_id' => $user->id,
                'variant_id' => $variantId,
                'bulk_qty' => $bulkQty,
                'qty' => $newTotalQty,
                'order_qty' => $newOrderQty,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Bulk cart quantity updated successfully.',
            'data' => [
                'cartId' => $cartItem->id,
                'variantId' => $cartItem->variant_id,
                'qty' => (int) $cartItem->qty,
                'orderQty' => (int) $cartItem->order_qty,
                'bulkQty' => (int) $cartItem->bulk_qty
            ]
        ], 200);
    }

    public function getBulkCart(Request $request)
    {
        $user = auth('api')->user() ?? auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
        }

        $cartItems = BulkCart::with(['variant.product.brand'])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Your bulk cart is empty.',
                'data' => ['items' => [], 'summary' => null]
            ], 200);
        }

        $subTotal = 0;
        $totalQuantity = 0;

        $formattedItems = $cartItems->map(function ($item) use (&$subTotal, &$totalQuantity) {
            $variant = $item->variant;
            $product = $variant->product;

            $activePrice = ($variant->offer_price > 0) ? $variant->offer_price : $variant->price;
            $itemSubTotal = $activePrice * $item->qty;

            $subTotal += $itemSubTotal;
            $totalQuantity += $item->qty;

            $bulkQty = (int) ($item->bulk_qty ?: ($item->order_qty > 0 ? floor($item->qty / $item->order_qty) : $item->qty));

            return [
                'cartItemId' => $item->id,
                'variantId' => $variant->id,
                'productName' => $product->name,
                'variantName' => $variant->variant_name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                'activePrice' => (float) $activePrice,
                'bulkQty' => $bulkQty,
                'qty' => (int) $item->qty,
                'orderQty' => (int) $item->order_qty,
                'unit' => $variant->unit,
                'itemTotal' => (float) $itemSubTotal
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Bulk cart retrieved successfully.',
            'data' => [
                'items' => $formattedItems,
                'summary' => [
                    'subTotal' => round((float) $subTotal, 2),
                    'totalItemsCount' => $cartItems->count(),
                    'totalQuantity' => $totalQuantity
                ]
            ]
        ], 200);
    }

    public function requestQuote(Request $request)
    {
        // (Unchanged from original)
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'delivery_location' => 'required|string|min:5|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'preferred_delivery_date' => 'required|date|after_or_equal:today',
            'payment_preference' => 'required|string|in:Online,Cash on Delivery,Bank Transfer',
            'full_name' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z]+(?:\s[a-zA-Z]+)*$/'
            ],
            'phone_number' => [
                'required',
                'string',
                'regex:/^[6-9][0-9]{9}$/'
            ],
            'gstin' => [
                'nullable',
                'string',
                'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/'
            ]
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation Error', 'errors' => $validator->errors()], 422);
        }

        $cartItems = BulkCart::where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Your bulk cart is empty.'], 400);
        }

        try {
            $quoteId = DB::transaction(function () use ($user, $request, $cartItems) {
                $quoteRequest = BulkQuoteRequest::create([
                    'user_id' => $user->id,
                    'delivery_location' => $request->delivery_location,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'preferred_delivery_date' => $request->preferred_delivery_date,
                    'payment_preference' => $request->payment_preference,
                    'full_name' => $request->full_name,
                    'phone_number' => $request->phone_number,
                    'gstin' => $request->gstin,
                    'status' => 'Pending'
                ]);

                foreach ($cartItems as $item) {
                    BulkQuoteRequestItem::create([
                        'bulk_quote_request_id' => $quoteRequest->id,
                        'variant_id' => $item->variant_id,
                        'qty' => $item->qty,
                        'order_qty' => $item->order_qty
                    ]);
                }

                BulkCart::where('user_id', $user->id)->delete();

                return $quoteRequest->id;
            });

            $newQuote = BulkQuoteRequest::with(['items.variant.product.brand'])->find($quoteId);

            $productIds = $newQuote->items->map(function ($item) {
                return $item->variant && $item->variant->product ? $item->variant->product->id : null;
            })->filter()->unique()->toArray();

            $productRatings = \App\Models\ProductRating::whereIn('product_id', $productIds)
                ->selectRaw('product_id, AVG(rating) as avg_rating')
                ->groupBy('product_id')
                ->pluck('avg_rating', 'product_id');

            $formattedItems = $newQuote->items->map(function ($item) use ($productRatings) {
                $variant = $item->variant;
                $product = $variant ? $variant->product : null;

                $activePrice = 0;
                if ($variant) {
                    $activePrice = ($variant->offer_price > 0) ? $variant->offer_price : $variant->price;
                }

                $unit = $variant ? $variant->unit : 'Units';
                $baseBulkQty = $item->order_qty > 0 ? (int) floor($item->qty / $item->order_qty) : $item->qty;

                $calculatedRating = ($product && isset($productRatings[$product->id]))
                    ? round((float) $productRatings[$product->id], 1)
                    : 0;

                return [
                    'itemId' => $item->id,
                    'variantId' => $variant ? $variant->id : null,
                    'productName' => $product ? $product->name : 'Unknown Product',
                    'variantName' => $variant ? $variant->variant_name : 'Unknown Variant',
                    'brandName' => ($product && $product->brand) ? $product->brand->brand_name : null,
                    'rating' => $calculatedRating,
                    'qty' => (int) $item->qty,
                    'orderQty' => (int) $item->order_qty,
                    'unit' => $unit,
                    'activePrice' => (float) $activePrice,
                    'itemTotal' => round((float) $activePrice * (int) $item->qty, 2),
                    'qtyText' => $baseBulkQty . ' * ' . $item->order_qty . ' ' . $unit,
                    'totalQtyText' => 'Qty: ' . $item->qty . ' ' . $unit,
                    'image' => ($variant && $variant->image)
                        ? asset('uploads/products/' . $variant->image)
                        : (($product && $product->image) ? asset('uploads/products/' . $product->image) : null),
                ];
            });

            $displayOrderId = '#ORD' . str_pad($newQuote->id, 5, '0', STR_PAD_LEFT);
            $formattedDeliveryDate = \Carbon\Carbon::parse($newQuote->preferred_delivery_date)->format('d M, Y');
            $totalItemsCount = $formattedItems->count();

            // $formattedQuote = [
            //     'quoteId' => $newQuote->id,
            //     'displayOrderId' => '#ORD' . str_pad($newQuote->id, 5, '0', STR_PAD_LEFT),
            //     'deliveryLocation' => $newQuote->delivery_location,
            //     'latitude' => (float) $newQuote->latitude,
            //     'longitude' => (float) $newQuote->longitude,
            //     'status' => $newQuote->status,
            //     'statusLabel' => $this->statusMap()[$newQuote->status] ?? $newQuote->status,
            //     'preferredDeliveryDate' => \Carbon\Carbon::parse($newQuote->preferred_delivery_date)->format('d M, Y'),
            //     'paymentPreference' => $newQuote->payment_preference,
            //     'requestedOn' => $newQuote->created_at->format('d M, Y, h:i A'),
            //     'totalItemsCount' => $formattedItems->count(),
            //     'items' => $formattedItems
            // ];

            $formattedQuote = [
                'quoteId' => $newQuote->id,
                'displayOrderId' => $displayOrderId,
                'deliveryLocation' => $newQuote->delivery_location,
                'latitude' => (float) $newQuote->latitude,
                'longitude' => (float) $newQuote->longitude,
                'status' => $newQuote->status,
                'statusLabel' => $this->statusMap()[$newQuote->status] ?? $newQuote->status,
                'preferredDeliveryDate' => $formattedDeliveryDate,
                'paymentPreference' => $newQuote->payment_preference,
                'requestedOn' => $newQuote->created_at->format('d M, Y, h:i A'),
                'totalItemsCount' => $totalItemsCount,
                'items' => $formattedItems
            ];

            // 1. Bulk Quote Request Confirmation Notification
            $quoteNotificationMessage = "We have received your bulk quote request {$displayOrderId} for {$totalItemsCount} item(s). The NirmaanNXT procurement team is reviewing your requirements to match you with top-rated, eligible vendors.";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Bulk Quote Request Received",
                $quoteNotificationMessage,
                'bulk_quote'
            );

            // 2. Delivery & Next-Step Details Notification
            $detailsNotificationMessage = "Request {$displayOrderId} summary: Target delivery by {$formattedDeliveryDate} ({$newQuote->payment_preference}). You will be notified as soon as competitive quotes are ready for your review.";
            $this->sendAndSaveNotification(
                $user->id,
                $user->device_token,
                "Next Steps for Your Quote",
                $detailsNotificationMessage,
                'bulk_quote'
            );

            return response()->json([
                'status' => true,
                'message' => 'Your quote request has been sent to vendors successfully.',
                'data' => [
                    'quoteId' => $quoteId,
                    'quote' => $formattedQuote
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while submitting the request.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function myBulkQuotes(Request $request)
    {
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $quotes = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'items.acceptance.vendor',
            'items.vendorQuotes.vendorQuote.vendor',
        ])
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        if ($quotes->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'No bulk quote requests found.',
                'data' => [
                    'quotes' => []
                ]
            ], 200);
        }

        $formattedQuotes = $quotes->map(function ($quote) {
            $items = $quote->items->map(function ($item) {
                $variant = $item->variant;
                $product = $variant ? $variant->product : null;

                return array_merge([
                    'itemId' => $item->id,
                    'variantId' => $variant ? $variant->id : null,
                    'productName' => $product ? $product->name : 'Unknown Product',
                    'variantName' => $variant ? $variant->variant_name : 'Unknown Variant',
                    'brandName' => ($product && $product->brand) ? $product->brand->brand_name : null,
                    'qty' => (int) $item->qty,
                    'orderQty' => (int) $item->order_qty,
                    'unit' => $variant ? $variant->unit : 'Units',
                    'image' => ($variant && $variant->image)
                        ? asset('uploads/products/' . $variant->image)
                        : (($product && $product->image) ? asset('uploads/products/' . $product->image) : null),
                ], $this->formatItemAcceptanceAndVendorQuotes($item));
            });

            return [
                'quoteId' => $quote->id,
                'displayOrderId' => '#ORD' . str_pad($quote->id, 5, '0', STR_PAD_LEFT),
                'deliveryLocation' => $quote->delivery_location,
                'latitude' => (float) $quote->latitude,
                'longitude' => (float) $quote->longitude,
                'status' => $quote->status,
                'statusLabel' => $this->statusMap()[$quote->status] ?? $quote->status,
                'preferredDeliveryDate' => \Carbon\Carbon::parse($quote->preferred_delivery_date)->format('d M, Y'),
                'paymentPreference' => $quote->payment_preference,
                'requestedOn' => $quote->created_at->format('d M, Y, h:i A'),
                'totalItemsCount' => $items->count(),
                'isNotify' => (bool) $quote->is_notify,
                'items' => $items
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Bulk quotes retrieved successfully.',
            'data' => [
                'quotes' => $formattedQuotes
            ]
        ], 200);
    }

    public function getBulkQuoteById(Request $request)
    {
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quoteId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $quote = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'items.acceptance.vendor',
            'items.vendorQuotes.vendorQuote.vendor',
        ])
            ->where('user_id', $user->id)
            ->where('id', $request->quoteId)
            ->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote request not found or unauthorized.'
            ], 404);
        }

        $items = $quote->items->map(function ($item) {
            $variant = $item->variant;
            $product = $variant ? $variant->product : null;

            return array_merge([
                'itemId' => $item->id,
                'variantId' => $variant ? $variant->id : null,
                'productName' => $product ? $product->name : 'Unknown Product',
                'variantName' => $variant ? $variant->variant_name : 'Unknown Variant',
                'brandName' => ($product && $product->brand) ? $product->brand->brand_name : null,
                'qty' => (int) $item->qty,
                'orderQty' => (int) $item->order_qty,
                'unit' => $variant ? $variant->unit : 'Units',
                'image' => ($variant && $variant->image)
                    ? asset('uploads/products/' . $variant->image)
                    : (($product && $product->image) ? asset('uploads/products/' . $product->image) : null),
            ], $this->formatItemAcceptanceAndVendorQuotes($item));
        });

        $formattedQuote = [
            'quoteId' => $quote->id,
            'displayOrderId' => '#ORD' . str_pad($quote->id, 5, '0', STR_PAD_LEFT),
            'deliveryLocation' => $quote->delivery_location,
            'latitude' => (float) $quote->latitude,
            'longitude' => (float) $quote->longitude,
            'status' => $quote->status,
            'statusLabel' => $this->statusMap()[$quote->status] ?? $quote->status,
            'preferredDeliveryDate' => \Carbon\Carbon::parse($quote->preferred_delivery_date)->format('d M, Y'),
            'paymentPreference' => $quote->payment_preference,
            'requestedOn' => $quote->created_at->format('d M, Y, h:i A'),
            'totalItemsCount' => $items->count(),
            'totalPrice' => round($this->calculateQuoteEstimatedTotal($quote), 2),
            'isNotify' => (bool) $quote->is_notify,
            'items' => $items
        ];

        return response()->json([
            'status' => true,
            'message' => 'Bulk quote details retrieved successfully.',
            'data' => [
                'quote' => $formattedQuote
            ]
        ], 200);
    }

    public function getBulkFilterOptions(Request $request)
    {
        // (Unchanged from original)
        $productQuery = Product::where('status', 1)
            ->where('trash', 0)
            ->whereHas('variants.vendorBulkProducts', function ($q) {
                $q->where('status', 1)->where('trash', 0);
            });

        if ($request->filled('categoryId')) {
            $productQuery->where('category_id', $request->categoryId);
        }
        if ($request->filled('brandId')) {
            $productQuery->where('brand_id', $request->brandId);
        }
        if ($request->filled('subCategoryId')) {
            $productQuery->where('subcategory_id', $request->subCategoryId);
        }

        $filteredProductIds = clone $productQuery->pluck('id');
        $availableBrandIds = (clone $productQuery)->distinct()->pluck('brand_id')->filter();
        $availableCategoryIds = (clone $productQuery)->distinct()->pluck('category_id')->filter();
        $availableSubCategoryIds = (clone $productQuery)->distinct()->pluck('subcategory_id')->filter();

        $brands = Brand::whereIn('id', $availableBrandIds)->where('status', 1)->where('trash', 0)->get(['id', 'brand_name']);
        $categories = ProductCategory::whereIn('id', $availableCategoryIds)->where('status', 1)->where('trash', 0)->get(['id', 'name']);
        $subCategories = ProductSubcategory::whereIn('id', $availableSubCategoryIds)->where('status', 1)->where('trash', 0)->get(['id', 'name']);

        $filters = [];

        if ($brands->isNotEmpty()) {
            $filters[] = [
                'filterKey' => 'brandId',
                'filterName' => 'Brand',
                'type' => 'checkbox',
                'options' => $brands->map(fn($brand) => ['id' => (string) $brand->id, 'label' => $brand->brand_name])->values()
            ];
        }

        $minBulkQty = min($this->bulkQuantityOptions);
        $maxBulkQty = max($this->bulkQuantityOptions);
        $rawMinPrice = (float) (clone $productQuery)->min(DB::raw('COALESCE(NULLIF(offer_price, 0), price)')) * $minBulkQty;
        $rawMaxPrice = (float) (clone $productQuery)->max(DB::raw('COALESCE(NULLIF(offer_price, 0), price)')) * $maxBulkQty;

        $priceOptions = [];

        if ($rawMaxPrice > $rawMinPrice) {
            $diff = $rawMaxPrice - $rawMinPrice;
            $factor = $diff > 1000000 ? 100000 : ($diff > 100000 ? 10000 : ($diff > 10000 ? 1000 : ($diff > 1000 ? 500 : ($diff > 100 ? 100 : ($diff > 50 ? 50 : ($diff > 10 ? 10 : 1))))));
            $roundedMin = floor($rawMinPrice / $factor) * $factor;
            $roundedMax = ceil($rawMaxPrice / $factor) * $factor;
            $step = max($factor, ceil(($roundedMax - $roundedMin) / 4 / $factor) * $factor);

            for ($i = 0; $i < 4; $i++) {
                $start = $roundedMin + ($i * $step);
                $end = $start + $step;
                if ($i === 3 || $end >= $roundedMax) {
                    $priceOptions[] = ['id' => $start . '-' . $roundedMax, 'label' => '₹' . $start . ' - ₹' . $roundedMax];
                    break;
                }
                $priceOptions[] = ['id' => $start . '-' . $end, 'label' => '₹' . $start . ' - ₹' . $end];
            }
        } elseif ($rawMinPrice === $rawMaxPrice && $rawMinPrice > 0) {
            $roundedPrice = round($rawMinPrice);
            $priceOptions[] = ['id' => $roundedPrice . '-' . $roundedPrice, 'label' => '₹' . $roundedPrice];
        }

        if (!empty($priceOptions)) {
            $filters[] = ['filterKey' => 'priceRange', 'filterName' => 'Price', 'type' => 'radio', 'options' => $priceOptions];
        }

        if ($subCategories->isNotEmpty()) {
            $filters[] = [
                'filterKey' => 'subCategoryId',
                'filterName' => 'Grade',
                'type' => 'checkbox',
                'options' => $subCategories->map(fn($sub) => ['id' => (string) $sub->id, 'label' => $sub->name])->values()
            ];
        }

        if ($categories->isNotEmpty()) {
            $filters[] = [
                'filterKey' => 'categoryId',
                'filterName' => 'Material Type',
                'type' => 'checkbox',
                'options' => $categories->map(fn($cat) => ['id' => (string) $cat->id, 'label' => $cat->name])->values()
            ];
        }

        $variantsAttributes = ProductVariant::whereIn('product_id', $filteredProductIds)
            ->where('status', 1)->where('trash', 0)
            ->whereHas('vendorBulkProducts', function ($vq) {
                $vq->where('status', 1)->where('trash', 0);
            })
            ->whereNotNull('attributes')->pluck('attributes');

        $availableColors = [];
        foreach ($variantsAttributes as $attr) {
            $attrData = is_string($attr) ? json_decode($attr, true) : $attr;
            if (is_array($attrData) && !empty($attrData['Color'])) {
                $availableColors[] = trim($attrData['Color']);
            }
        }
        $availableColors = array_values(array_unique($availableColors));

        if (!empty($availableColors)) {
            $filters[] = [
                'filterKey' => 'colors',
                'filterName' => 'Colour',
                'type' => 'checkbox',
                'options' => array_map(fn($color) => ['id' => $color, 'label' => $color], $availableColors)
            ];
        }

        $hasRatings = \App\Models\ProductRating::whereIn('product_id', $filteredProductIds)->exists();
        if ($hasRatings) {
            $filters[] = [
                'filterKey' => 'rating',
                'filterName' => 'Rating',
                'type' => 'radio',
                'options' => [
                    ['id' => '4', 'label' => '4 Stars & Above'],
                    ['id' => '3', 'label' => '3 Stars & Above'],
                    ['id' => '2', 'label' => '2 Stars & Above'],
                ]
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Filter options retrieved successfully.',
            'data' => ['filters' => $filters]
        ], 200);
    }

    public function getBulkSortOptions(Request $request)
    {
        $sortOptions = [
            ['id' => 'popularity', 'label' => 'Popularity'],
            ['id' => 'price_asc', 'label' => 'Price: Low to High'],
            ['id' => 'price_desc', 'label' => 'Price: High to Low'],
            ['id' => 'newest', 'label' => 'Newest Arrivals']
        ];

        return response()->json([
            'status' => true,
            'message' => 'Sort options retrieved successfully.',
            'data' => [
                'sortOptions' => $sortOptions
            ]
        ], 200);
    }

    public function getBulkQuoteVendorQuotes(Request $request)
    {
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quoteId' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $quote = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'items.variant.product.category',
        ])
            ->where('user_id', $user->id)
            ->where('id', $request->quoteId)
            ->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote request not found or unauthorized.'
            ], 404);
        }

        $originalAmount = $this->calculateQuoteEstimatedTotal($quote);
        $deliveryEstimate = $this->buildDeliveryEstimateLabel($quote->preferred_delivery_date);

        $assignments = BulkQuoteRequestVendor::with(['vendor', 'itemQuotes'])
            ->where('bulk_quote_request_id', $quote->id)
            ->whereIn('status', ['Accepted', 'Quoted'])
            ->get()
            ->sortBy(function ($assignment) {
                $total = $this->vendorQuotePayable($assignment);
                return $total !== null ? $total : PHP_FLOAT_MAX;
            })
            ->values();

        $itemsById = $quote->items->keyBy('id');
        $bestVendorId = null;

        foreach ($assignments as $assignment) {
            if ($this->vendorQuotePayable($assignment) !== null) {
                $bestVendorId = $assignment->vendor_id;
                break;
            }
        }

        $vendorQuotes = $assignments->map(function ($assignment) use ($itemsById, $originalAmount, $deliveryEstimate, $bestVendorId) {
            return $this->formatVendorQuote(
                $assignment,
                $itemsById,
                $originalAmount,
                $deliveryEstimate,
                $assignment->vendor_id === $bestVendorId
            );
        })->values();

        $bestQuote = ($bestVendorId !== null) ? $vendorQuotes->first() : null;

        return response()->json([
            'status' => true,
            'message' => $vendorQuotes->isEmpty()
                ? 'No vendor quotes received yet.'
                : 'Vendor quotes retrieved successfully.',
            'data' => [
                'quote' => $this->formatBulkQuoteHeader($quote, $originalAmount),
                'bestQuote' => $bestQuote,
                'vendorQuotes' => $vendorQuotes,
                'categoryQuotes' => $this->buildCategoryVendorQuotes($quote, $assignments),
            ]
        ], 200);
    }

    public function getBulkQuoteVendorQuoteDetails(Request $request)
    {
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quoteId' => 'required|integer',
            'vendorId' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $quote = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'items.variant.product.category',
        ])
            ->where('user_id', $user->id)
            ->where('id', $request->quoteId)
            ->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote request not found or unauthorized.'
            ], 404);
        }

        $originalAmount = $this->calculateQuoteEstimatedTotal($quote);
        $deliveryEstimate = $this->buildDeliveryEstimateLabel($quote->preferred_delivery_date);

        $assignments = BulkQuoteRequestVendor::with(['vendor', 'itemQuotes'])
            ->where('bulk_quote_request_id', $quote->id)
            ->whereIn('status', ['Accepted', 'Quoted'])
            ->get()
            ->sortBy(function ($assignment) {
                $total = $this->vendorQuotePayable($assignment);
                return $total !== null ? $total : PHP_FLOAT_MAX;
            })
            ->values();

        $itemsById = $quote->items->keyBy('id');

        $selectedAssignment = $request->filled('vendorId')
            ? $assignments->firstWhere('vendor_id', (int) $request->vendorId)
            : $assignments->first();

        if (!$selectedAssignment) {
            return response()->json([
                'status' => false,
                'message' => 'Vendor quote not found for this quote request.'
            ], 404);
        }

        $bestVendorId = null;

        foreach ($assignments as $assignment) {
            if ($this->vendorQuotePayable($assignment) !== null) {
                $bestVendorId = $assignment->vendor_id;
                break;
            }
        }

        $vendorQuotes = $assignments->map(function ($assignment) use ($itemsById, $originalAmount, $deliveryEstimate, $bestVendorId) {
            return $this->formatVendorQuote(
                $assignment,
                $itemsById,
                $originalAmount,
                $deliveryEstimate,
                $assignment->vendor_id === $bestVendorId
            );
        })->values();

        $selectedVendor = $this->formatVendorQuote(
            $selectedAssignment,
            $itemsById,
            $originalAmount,
            $deliveryEstimate,
            $selectedAssignment->vendor_id === $bestVendorId
        );

        $vendorSubTotal = (float) $selectedVendor['baseAmount'];
        $gstAmount = (float) $selectedVendor['gstAmount'];
        $deliveryCharges = (float) $selectedVendor['deliveryCharges'];
        $totalPayable = (float) $selectedVendor['totalPayable'];
        $totalSavings = (float) $selectedVendor['savings'];

        return response()->json([
            'status' => true,
            'message' => 'Vendor quote details retrieved successfully.',
            'data' => [
                'quote' => $this->formatBulkQuoteHeader($quote, $originalAmount),
                'selectedVendor' => $selectedVendor,
                'vendorQuotes' => $vendorQuotes,
                'billSummary' => [
                    'itemsCount' => $selectedVendor['itemsCount'],
                    'subTotalAmount' => round($vendorSubTotal, 2),
                    'bulkDiscount' => 0,
                    'deliveryCharges' => $deliveryCharges,
                    'gstPercentage' => 0, // Itemized
                    'taxesGst' => $gstAmount,
                    'totalSavings' => $totalSavings,
                    'totalPayable' => $totalPayable,
                ],
                'deliveryAddress' => [
                    'location' => $quote->delivery_location,
                    'label' => null,
                ],
            ]
        ], 200);
    }

    protected function formatBulkQuoteHeader($quote, float $originalAmount): array
    {
        return [
            'quoteId' => $quote->id,
            'displayOrderId' => '#ORD' . str_pad($quote->id, 5, '0', STR_PAD_LEFT),
            'deliveryLocation' => $quote->delivery_location,
            'latitude' => (float) $quote->latitude,
            'longitude' => (float) $quote->longitude,
            'status' => $quote->status,
            'statusLabel' => $this->statusMap()[$quote->status] ?? $quote->status,
            'preferredDeliveryDate' => $quote->preferred_delivery_date
                ? Carbon::parse($quote->preferred_delivery_date)->format('d M, Y')
                : null,
            'paymentPreference' => $quote->payment_preference,
            'requestedOn' => $quote->created_at->format('d M, Y, h:i A'),
            'totalItemsCount' => $quote->items->count(),
            'originalAmount' => round($originalAmount, 2),
        ];
    }

    protected function formatVendorQuote(BulkQuoteRequestVendor $assignment, $itemsById, float $originalAmount, ?string $deliveryEstimate, bool $isBestPrice): array
    {
        $vendor = $assignment->vendor;
        $items = $this->formatVendorQuoteItemLines($assignment, $itemsById);

        if (count($items) > 0) {
            $baseAmount = round((float) array_sum(array_column($items, 'quotedAmount')), 2);
            $gstAmount = round((float) array_sum(array_column($items, 'gstAmount')), 2);
            $deliveryCharges = round((float) array_sum(array_column($items, 'deliveryCharges')), 2);
            $vendorOriginal = round((float) array_sum(array_column($items, 'originalAmount')), 2);
        } else {
            $baseAmount = $assignment->quoted_amount !== null ? (float) $assignment->quoted_amount : null;
            $gstAmount = 0;
            $deliveryCharges = 0;
            $vendorOriginal = round($originalAmount, 2);
        }

        $savings = ($baseAmount !== null && $vendorOriginal > 0)
            ? max(0, round($vendorOriginal - $baseAmount, 2))
            : 0;

        $totalPayable = $baseAmount !== null
            ? round((float) $baseAmount + $gstAmount + $deliveryCharges, 2)
            : null;

        return [
            'vendorId' => $assignment->vendor_id,
            'businessName' => $vendor ? $vendor->business_name : 'Unknown Vendor',
            'contactPerson' => $vendor ? $vendor->contact_person : null,
            'logo' => ($vendor && $vendor->company_logo)
                ? asset('uploads/company_logos/' . $vendor->company_logo)
                : null,
            'address' => $vendor ? $vendor->address : null,
            'rating' => null,
            'status' => $assignment->status,
            'itemsCount' => count($items),
            'items' => $items,
            'baseAmount' => $baseAmount,
            'gstPercentage' => 0, // Maintained per-item now
            'gstAmount' => $gstAmount,
            'deliveryCharges' => $deliveryCharges,
            'quotedAmount' => $totalPayable,
            'totalPayable' => $totalPayable,
            'originalAmount' => $vendorOriginal,
            'savings' => $savings,
            'deliveryEstimate' => $deliveryEstimate,
            'notes' => $assignment->vendor_notes,
            'respondedAt' => $assignment->responded_at
                ? Carbon::parse($assignment->responded_at)->format('d M, Y, h:i A')
                : null,
            'expectedDeliveryDate' => $assignment->expected_delivery_date
                ? $assignment->expected_delivery_date->format('d-m-Y')
                : null,
            'isBestPrice' => $isBestPrice,
        ];
    }

    protected function formatVendorQuoteItemLines(BulkQuoteRequestVendor $assignment, $itemsById): array
    {
        return $assignment->itemQuotes
            ->groupBy('variant_id')
            ->map(function ($itemQuotes, $variantId) use ($itemsById) {
                $firstQuote = $itemQuotes->first();
                $firstItem = $firstQuote ? $itemsById->get($firstQuote->bulk_quote_request_item_id) : null;
                $variant = $firstItem ? $firstItem->variant : null;
                $product = $variant ? $variant->product : null;

                $qty = 0;
                $quotedAmount = 0;
                $gstAmount = 0;
                $deliveryCharges = 0;
                $originalAmount = 0;

                foreach ($itemQuotes as $itemQuote) {
                    $item = $itemsById->get($itemQuote->bulk_quote_request_item_id);
                    $itemQty = $item ? (int) $item->qty : 0;

                    $qty += $itemQty;

                    // The pure base quote amount (excluding GST/Delivery)
                    $base = $itemQuote->quoted_amount !== null
                        ? (float) $itemQuote->quoted_amount
                        : ($itemQuote->quoted_unit_price !== null ? (float) $itemQuote->quoted_unit_price * $itemQty : 0);

                    $quotedAmount += $base;
                    $gstAmount += round($base * ((float) $itemQuote->gst_percentage / 100), 2);
                    $deliveryCharges += (float) $itemQuote->delivery_charges;
                    $originalAmount += $item ? $this->itemEstimatedTotal($item) : 0;
                }

                $quotedUnitPrice = $firstQuote && $firstQuote->quoted_unit_price !== null
                    ? (float) $firstQuote->quoted_unit_price
                    : null;

                $itemIds = $itemQuotes
                    ->pluck('bulk_quote_request_item_id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

                return [
                    'itemId' => $itemIds[0] ?? null,
                    'itemIds' => $itemIds,
                    'variantId' => (int) $variantId,
                    'productName' => $product ? $product->name : 'Unknown Product',
                    'variantName' => $variant ? $variant->variant_name : 'Unknown Variant',
                    'brandName' => ($product && $product->brand) ? $product->brand->brand_name : null,
                    'categoryId' => ($product && $product->category) ? (int) $product->category->id : null,
                    'categoryName' => ($product && $product->category) ? $product->category->name : null,
                    'qty' => $qty,
                    'unit' => $variant ? $variant->unit : 'Units',
                    'image' => $this->quoteItemImageUrl($firstItem),
                    'quotedUnitPrice' => $quotedUnitPrice,
                    'quotedAmount' => round($quotedAmount, 2), // Sent strictly as Base Quoted Item Price
                    'gstAmount' => round($gstAmount, 2),
                    'deliveryCharges' => round($deliveryCharges, 2),
                    'originalAmount' => round($originalAmount, 2),
                ];
            })
            ->values()
            ->all();
    }

    protected function quoteItemImageUrl($item): ?string
    {
        if (!$item)
            return null;

        $variant = $item->variant;
        $product = $variant ? $variant->product : null;

        if ($variant && $variant->image) {
            return asset('uploads/products/' . $variant->image);
        }

        if ($product && $product->image) {
            return asset('uploads/products/' . $product->image);
        }

        return null;
    }

    protected function vendorQuoteTotal($assignment): ?float
    {
        if ($assignment->relationLoaded('itemQuotes') && $assignment->itemQuotes->isNotEmpty()) {
            return round((float) $assignment->itemQuotes->sum('quoted_amount'), 2);
        }

        return $assignment->quoted_amount !== null ? (float) $assignment->quoted_amount : null;
    }

    protected function vendorQuotePayable($assignment): ?float
    {
        if ($assignment->relationLoaded('itemQuotes') && $assignment->itemQuotes->isNotEmpty()) {
            $payable = 0;
            foreach ($assignment->itemQuotes as $itemQuote) {
                $base = (float) $itemQuote->quoted_amount;
                $gstAmt = round($base * ((float) $itemQuote->gst_percentage / 100), 2);
                $payable += ($base + $gstAmt + (float) $itemQuote->delivery_charges);
            }
            return round($payable, 2);
        }

        $base = $this->vendorQuoteTotal($assignment);
        if ($base === null)
            return null;

        return round($base, 2);
    }

    protected function buildCategoryVendorQuotes($quote, $assignments): array
    {
        $groupedItems = $quote->items->groupBy(function ($item) {
            return ($item->variant && $item->variant->product)
                ? (int) $item->variant->product->category_id
                : 0;
        });

        $categories = [];

        foreach ($groupedItems as $categoryId => $items) {
            $firstItem = $items->first();
            $category = ($firstItem && $firstItem->variant && $firstItem->variant->product)
                ? $firstItem->variant->product->category
                : null;

            $categoryEstimatedAmount = $items->sum(function ($item) {
                return $this->itemEstimatedTotal($item);
            });

            $categoryItemIds = $items->pluck('id')->map(fn($id) => (int) $id)->values()->all();

            $categoryItems = $items->map(function ($item) {
                $variant = $item->variant;
                $product = $variant ? $variant->product : null;

                return [
                    'itemId' => $item->id,
                    'variantId' => $item->variant_id,
                    'productName' => $product ? $product->name : 'Unknown Product',
                    'variantName' => $variant ? $variant->variant_name : 'Unknown Variant',
                    'qty' => (int) $item->qty,
                    'unit' => $variant ? $variant->unit : 'Units',
                    'image' => $this->quoteItemImageUrl($item),
                ];
            })->values();

            $categoryProductImages = $categoryItems
                ->pluck('image')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $vendorRows = [];

            foreach ($assignments as $assignment) {
                $vendorItems = $assignment->itemQuotes->filter(function ($itemQuote) use ($categoryItemIds) {
                    return in_array((int) $itemQuote->bulk_quote_request_item_id, $categoryItemIds, true);
                });

                if ($vendorItems->isEmpty()) {
                    continue;
                }

                $subtotal = round((float) $vendorItems->sum('quoted_amount'), 2);

                $gstAmount = 0;
                $deliveryCharges = 0;
                foreach ($vendorItems as $vi) {
                    $base = (float) $vi->quoted_amount;
                    $gstAmount += round($base * ((float) $vi->gst_percentage / 100), 2);
                    $deliveryCharges += (float) $vi->delivery_charges;
                }

                $vendorRows[] = [
                    'assignment' => $assignment,
                    'subtotal' => $subtotal,
                    'gstAmount' => round($gstAmount, 2),
                    'deliveryCharges' => round($deliveryCharges, 2),
                    'payable' => round($subtotal + $gstAmount + $deliveryCharges, 2),
                    'itemsCount' => $vendorItems->count(),
                ];
            }

            usort($vendorRows, function ($a, $b) {
                return $a['payable'] <=> $b['payable'];
            });

            $bestPayable = !empty($vendorRows) ? $vendorRows[0]['payable'] : null;

            $categoryVendorQuotes = array_map(function ($row) use ($bestPayable) {
                $assignment = $row['assignment'];
                $vendor = $assignment->vendor;

                return [
                    'vendorId' => $assignment->vendor_id,
                    'businessName' => $vendor ? $vendor->business_name : 'Unknown Vendor',
                    'logo' => ($vendor && $vendor->company_logo)
                        ? asset('uploads/company_logos/' . $vendor->company_logo)
                        : null,
                    'status' => $assignment->status,
                    'itemsCount' => $row['itemsCount'],
                    'baseAmount' => $row['subtotal'],
                    'gstPercentage' => 0, // Deprecated at category level
                    'gstAmount' => $row['gstAmount'],
                    'deliveryCharges' => $row['deliveryCharges'],
                    'quotedAmount' => $row['payable'],
                    'isBestPrice' => $bestPayable !== null && $row['payable'] === $bestPayable,
                ];
            }, $vendorRows);

            $categories[] = [
                'categoryId' => ((int) $categoryId) ?: null,
                'categoryName' => $category ? $category->name : 'Uncategorised',
                'itemsCount' => $items->count(),
                'totalQty' => (int) $items->sum('qty'),
                'estimatedAmount' => round((float) $categoryEstimatedAmount, 2),
                'image' => $categoryItems->first()['image'] ?? null,
                'productImages' => $categoryProductImages,
                'items' => $categoryItems,
                'vendorsCount' => count($vendorRows),
                'bestPrice' => $bestPayable,
                'vendorQuotes' => $categoryVendorQuotes,
            ];
        }

        return $categories;
    }

    protected function calculateQuoteEstimatedTotal($quote): float
    {
        return (float) $quote->items->sum(function ($item) {
            return $this->itemEstimatedTotal($item);
        });
    }

    protected function itemEstimatedTotal($item): float
    {
        $variant = $item->variant;
        if (!$variant)
            return 0.0;

        $activePrice = ((float) $variant->offer_price > 0)
            ? (float) $variant->offer_price
            : (float) $variant->price;

        return $activePrice * (int) $item->qty;
    }

    protected function buildDeliveryEstimateLabel($preferredDeliveryDate): ?string
    {
        if (!$preferredDeliveryDate)
            return null;

        $deliveryDate = Carbon::parse($preferredDeliveryDate)->startOfDay();
        $today = Carbon::today();

        if ($deliveryDate->isSameDay($today))
            return 'Delivery Today';
        if ($deliveryDate->isSameDay($today->copy()->addDay()))
            return 'Delivery Tomorrow';

        return 'Delivery by ' . $deliveryDate->format('d M, Y');
    }

    public function acceptBulkQuoteItem(Request $request)
    {
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'items' => 'nullable|array|min:1',
            'items.*.bulkItemId' => 'required_with:items|integer|exists:bulk_quote_request_items,id',
            'items.*.vendorId' => 'required_with:items|integer|exists:vendors,id',
            'bulkItemIds' => 'nullable|array|min:1',
            'bulkItemIds.*' => 'integer|exists:bulk_quote_request_items,id',
            'vendorIds' => 'nullable|array|min:1',
            'vendorIds.*' => 'integer|exists:vendors,id',
            'bulkItemId' => 'nullable|integer|exists:bulk_quote_request_items,id',
            'vendorId' => 'nullable|integer|exists:vendors,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $pairs = $this->normalizeAcceptPairs($request);

        if (empty($pairs)) {
            return response()->json([
                'status' => false,
                'message' => 'Please provide at least one item and vendor to accept.'
            ], 422);
        }

        $bulkItemIds = array_keys($pairs);
        $vendorIds = array_values(array_unique(array_values($pairs)));

        $items = BulkQuoteRequestItem::whereIn('id', $bulkItemIds)->get()->keyBy('id');
        $quoteIds = $items->pluck('bulk_quote_request_id')->unique()->values();

        if ($quoteIds->count() !== 1) {
            return response()->json([
                'status' => false,
                'message' => 'All items must belong to the same quote request.'
            ], 422);
        }

        $quote = BulkQuoteRequest::with(['items.variant.product'])
            ->where('user_id', $user->id)
            ->where('id', $quoteIds->first())
            ->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote request not found or unauthorized.'
            ], 404);
        }

        $allItemIds = $quote->items->pluck('id')->map(fn($id) => (int) $id)->values()->all();
        $providedItemIds = array_map('intval', array_keys($pairs));
        $missingItemIds = array_values(array_diff($allItemIds, $providedItemIds));

        if (!empty($missingItemIds)) {
            $missingItems = $quote->items
                ->filter(fn($item) => in_array((int) $item->id, $missingItemIds, true))
                ->map(function ($item) {
                    $variant = $item->variant;
                    $product = $variant ? $variant->product : null;
                    return [
                        'itemId' => (int) $item->id,
                        'productName' => $product ? $product->name : null,
                    ];
                })
                ->values();

            return response()->json([
                'status' => false,
                'message' => 'Every item in the quote must be accepted in one request. Some items are missing.',
                'errors' => [
                    'missingItemIds' => $missingItemIds,
                    'missingItems' => $missingItems,
                ],
            ], 422);
        }

        $vendorQuotes = BulkQuoteRequestVendor::with('vendor')
            ->where('bulk_quote_request_id', $quote->id)
            ->whereIn('vendor_id', $vendorIds)
            ->whereIn('status', ['Accepted', 'Quoted'])
            ->get()
            ->keyBy('vendor_id');

        $vendorQuoteIds = $vendorQuotes->pluck('id')->all();

        $vendorItemQuotes = BulkQuoteRequestVendorItem::whereIn('bulk_quote_request_vendor_id', $vendorQuoteIds)
            ->whereIn('bulk_quote_request_item_id', $bulkItemIds)
            ->get()
            ->keyBy(function ($row) {
                return $row->bulk_quote_request_vendor_id . '_' . $row->bulk_quote_request_item_id;
            });

        $existingAcceptances = BulkQuoteRequestItemAcceptance::whereIn('bulk_quote_request_item_id', $bulkItemIds)
            ->get()
            ->keyBy('bulk_quote_request_item_id');

        $toAccept = [];
        $skipped = [];
        $conflicts = [];
        $invalid = [];

        foreach ($pairs as $bulkItemId => $vendorId) {
            $vendorQuote = $vendorQuotes->get($vendorId);

            if (!$vendorQuote) {
                $invalid[] = [
                    'bulkItemId' => (int) $bulkItemId,
                    'vendorId' => (int) $vendorId,
                    'reason' => 'This vendor has not quoted on this request.',
                ];
                continue;
            }

            $vendorItemQuote = $vendorItemQuotes->get($vendorQuote->id . '_' . $bulkItemId);

            if (!$vendorItemQuote || $vendorItemQuote->quoted_amount === null) {
                $invalid[] = [
                    'bulkItemId' => (int) $bulkItemId,
                    'vendorId' => (int) $vendorId,
                    'reason' => 'This vendor has not quoted a price for this item.',
                ];
                continue;
            }

            $existing = $existingAcceptances->get($bulkItemId);

            if ($existing) {
                if ((int) $existing->vendor_id === (int) $vendorId) {
                    $skipped[] = [
                        'bulkItemId' => (int) $bulkItemId,
                        'vendorId' => (int) $vendorId,
                        'status' => $existing->status,
                        'statusLabel' => $existing->status,
                    ];
                } else {
                    $conflicts[] = [
                        'bulkItemId' => (int) $bulkItemId,
                        'requestedVendorId' => (int) $vendorId,
                        'acceptedVendorId' => (int) $existing->vendor_id,
                    ];
                }
                continue;
            }

            $toAccept[] = [
                'item' => $items->get($bulkItemId),
                'vendorQuote' => $vendorQuote,
                'vendorItemQuote' => $vendorItemQuote,
            ];
        }

        if (!empty($conflicts)) {
            return response()->json([
                'status' => false,
                'message' => 'Some items are already accepted from another vendor.',
                'errors' => $conflicts,
            ], 409);
        }

        if (!empty($invalid)) {
            return response()->json([
                'status' => false,
                'message' => 'Some items could not be accepted because the vendor has not quoted them.',
                'errors' => $invalid,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $acceptedItems = [];

            foreach ($toAccept as $row) {
                $acceptance = BulkQuoteRequestItemAcceptance::create([
                    'bulk_quote_request_id' => $quote->id,
                    'bulk_quote_request_item_id' => $row['item']->id,
                    'bulk_quote_request_vendor_id' => $row['vendorQuote']->id,
                    'vendor_id' => $row['vendorQuote']->vendor_id,
                    'quoted_unit_price' => (float) $row['vendorItemQuote']->quoted_unit_price,
                    'quoted_amount' => (float) $row['vendorItemQuote']->quoted_amount,
                    'gst_percentage' => (float) $row['vendorItemQuote']->gst_percentage,
                    'delivery_charges' => (float) $row['vendorItemQuote']->delivery_charges,
                    'status' => 'Quote Locked',
                    'accepted_at' => Carbon::now(),
                ]);

                $vendor = $row['vendorQuote']->vendor;

                $acceptedItems[] = [
                    'bulkItemId' => (int) $row['item']->id,
                    'vendorId' => (int) $row['vendorQuote']->vendor_id,
                    'vendorName' => $vendor ? $vendor->business_name : null,
                    'quotedUnitPrice' => (float) $acceptance->quoted_unit_price,
                    'quotedAmount' => (float) $acceptance->quoted_amount,
                    'status' => $acceptance->status,
                    'statusLabel' => $acceptance->status,
                ];
            }

            if (in_array($quote->status, ['Pending', 'Sent To Vendors', 'Quoted'], true)) {
                $quote->status = 'Quote Locked';
                $quote->save();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => count($acceptedItems) > 0
                    ? 'Vendor quotes accepted and locked successfully.'
                    : 'Selected items were already accepted from these vendors.',
                'data' => [
                    'quoteId' => $quote->id,
                    'acceptedCount' => count($acceptedItems),
                    'skippedCount' => count($skipped),
                    'items' => $acceptedItems,
                    'skipped' => $skipped,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to accept the vendor quotes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function normalizeAcceptPairs(Request $request): array
    {
        $pairs = [];

        if (is_array($request->input('items'))) {
            foreach ($request->input('items') as $row) {
                if (!is_array($row))
                    continue;

                $bulkItemId = (int) ($row['bulkItemId'] ?? $row['bulk_item_id'] ?? 0);
                $vendorId = (int) ($row['vendorId'] ?? $row['vendor_id'] ?? 0);

                if ($bulkItemId > 0 && $vendorId > 0 && !isset($pairs[$bulkItemId])) {
                    $pairs[$bulkItemId] = $vendorId;
                }
            }
        }

        if (empty($pairs) && is_array($request->input('bulkItemIds')) && is_array($request->input('vendorIds'))) {
            $bulkItemIds = array_values($request->input('bulkItemIds'));
            $vendorIds = array_values($request->input('vendorIds'));

            if (count($bulkItemIds) === count($vendorIds)) {
                foreach ($bulkItemIds as $index => $bulkItemId) {
                    $bulkItemId = (int) $bulkItemId;
                    $vendorId = (int) $vendorIds[$index];

                    if ($bulkItemId > 0 && $vendorId > 0 && !isset($pairs[$bulkItemId])) {
                        $pairs[$bulkItemId] = $vendorId;
                    }
                }
            }
        }

        if (empty($pairs) && $request->filled('bulkItemId') && $request->filled('vendorId')) {
            $pairs[(int) $request->bulkItemId] = (int) $request->vendorId;
        }

        return $pairs;
    }

    protected function formatAcceptanceResponse($acceptance, string $message)
    {
        $vendor = $acceptance->vendor;

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => [
                'bulkItemId' => (int) $acceptance->bulk_quote_request_item_id,
                'vendorId' => (int) $acceptance->vendor_id,
                'vendorName' => $vendor ? $vendor->business_name : null,
                'quotedUnitPrice' => (float) $acceptance->quoted_unit_price,
                'quotedAmount' => (float) $acceptance->quoted_amount,
                'status' => $acceptance->status,
                'statusLabel' => $acceptance->status,
                'acceptedAt' => $acceptance->accepted_at
                    ? Carbon::parse($acceptance->accepted_at)->format('d M, Y, h:i A')
                    : null,
            ]
        ], 200);
    }

    protected function formatItemAcceptanceAndVendorQuotes($item): array
    {
        $acceptance = $item->acceptance;
        $acceptedVendorId = $acceptance ? (int) $acceptance->vendor_id : null;

        $vendorQuotes = $item->vendorQuotes->map(function ($vendorItemQuote) use ($acceptedVendorId) {
            $vendorQuote = $vendorItemQuote->vendorQuote;
            $vendor = $vendorQuote ? $vendorQuote->vendor : null;

            return [
                'vendorId' => $vendorQuote ? $vendorQuote->vendor_id : null,
                'businessName' => $vendor ? $vendor->business_name : null,
                'quotedUnitPrice' => $vendorItemQuote->quoted_unit_price !== null
                    ? (float) $vendorItemQuote->quoted_unit_price
                    : null,
                'quotedAmount' => $vendorItemQuote->quoted_amount !== null
                    ? (float) $vendorItemQuote->quoted_amount
                    : null,
                'status' => $vendorQuote ? $vendorQuote->status : null,
                'statusLabel' => $vendorQuote ? $vendorQuote->status : null,
                'isAccepted' => $acceptedVendorId !== null
                    && $vendorQuote !== null
                    && (int) $vendorQuote->vendor_id === $acceptedVendorId,
                'expectedDeliveryDate' => ($vendorQuote && $vendorQuote->expected_delivery_date)
                    ? $vendorQuote->expected_delivery_date->format('d-m-Y')
                    : null,
            ];
        })->values();

        return [
            'status' => $acceptance ? $acceptance->status : 'Pending',
            'statusLabel' => $acceptance ? $acceptance->status : 'Pending',
            'isAccepted' => $acceptance !== null,
            'acceptedVendor' => $acceptance ? [
                'vendorId' => (int) $acceptance->vendor_id,
                'businessName' => $acceptance->vendor ? $acceptance->vendor->business_name : null,
                'quotedUnitPrice' => (float) $acceptance->quoted_unit_price,
                'quotedAmount' => (float) $acceptance->quoted_amount,
                'status' => $acceptance->status,
                'statusLabel' => $acceptance->status,
                'acceptedAt' => $acceptance->accepted_at
                    ? Carbon::parse($acceptance->accepted_at)->format('d M, Y, h:i A')
                    : null,
            ] : null,
            'vendorQuotes' => $vendorQuotes,
        ];
    }

    public function notifyBulkQuote(Request $request)
    {
        // (Unchanged from original)
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quoteId' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $quote = BulkQuoteRequest::where('user_id', $user->id)
            ->where('id', $request->quoteId)
            ->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote request not found or unauthorized.'
            ], 404);
        }

        $quote->is_notify = 1;
        $quote->save();

        return response()->json([
            'status' => true,
            'message' => 'You will be notified when vendors respond to this request.',
            'data' => [
                'quoteId' => $quote->id,
                'isNotify' => (bool) $quote->is_notify,
            ]
        ], 200);
    }

    public function getBulkQuoteLockedItems(Request $request)
    {
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quoteId' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $quote = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'items.variant.product.category',
            'order',
        ])
            ->where('user_id', $user->id)
            ->where('id', $request->quoteId)
            ->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote request not found or unauthorized.'
            ], 404);
        }

        $acceptances = BulkQuoteRequestItemAcceptance::with(['vendor', 'vendorQuote'])
            ->where('bulk_quote_request_id', $quote->id)
            ->get();

        return response()->json([
            'status' => true,
            'message' => $acceptances->isEmpty()
                ? 'No vendor items have been locked yet.'
                : 'Locked bulk order items retrieved successfully.',
            'data' => [
                'quote' => $this->formatBulkQuoteHeader($quote, $this->calculateQuoteOriginalTotal($quote)),
                'isConfirmed' => $quote->order !== null,
                'order' => $quote->order ? $this->formatBulkOrderSummary($quote->order) : null,
                'vendors' => $this->formatLockedItemsByVendor($acceptances, $quote),
                'billSummary' => $this->buildLockedBillSummary($acceptances, $quote),
                'deliveryAddress' => [
                    'location' => $quote->delivery_location,
                    'label' => null,
                ],
                'rewardPoints' => 2000,
            ]
        ], 200);
    }

    public function confirmBulkQuoteOrder(Request $request)
    {
        // (Unchanged from original except internal function calls match format)
        $user = auth()->user();

        if (!$user || (int) $user->status === 0 || (int) $user->status === 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'quoteId' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $quote = BulkQuoteRequest::with([
            'items.variant.product.brand',
            'items.variant.product.category',
            'order',
        ])
            ->where('user_id', $user->id)
            ->where('id', $request->quoteId)
            ->first();

        if (!$quote) {
            return response()->json([
                'status' => false,
                'message' => 'Quote request not found or unauthorized.'
            ], 404);
        }

        $acceptances = BulkQuoteRequestItemAcceptance::with(['vendor', 'vendorQuote'])
            ->where('bulk_quote_request_id', $quote->id)
            ->get();

        if ($acceptances->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No vendor quotes have been accepted for this request yet.'
            ], 400);
        }

        if ($quote->order) {
            return $this->formatOrderConfirmation($quote->order, $quote, $acceptances);
        }

        $billSummary = $this->buildLockedBillSummary($acceptances, $quote);

        try {
            DB::beginTransaction();

            $order = BulkQuoteOrder::create([
                'bulk_quote_request_id' => $quote->id,
                'user_id' => $user->id,
                'order_number' => 'PENDING',
                'transaction_id' => 'TIDN' . strtoupper(Str::random(12)),
                'sub_total' => $billSummary['subTotalAmount'],
                'gst_amount' => $billSummary['taxesGst'],
                'delivery_charges' => $billSummary['deliveryCharges'],
                'total_payable' => $billSummary['totalPayable'],
                'total_items' => $billSummary['itemsCount'],
                'total_quantity' => $billSummary['totalQuantity'],
                'status' => 'Confirmed',
                'confirmed_at' => Carbon::now(),
            ]);

            $order->order_number = '#ORN' . str_pad($order->id, 5, '0', STR_PAD_LEFT);
            $order->save();

            if (in_array($quote->status, ['Pending', 'Sent To Vendors', 'Quoted', 'Quote Locked'], true)) {
                $quote->status = 'Confirmed';
                $quote->save();
            }

            DB::commit();

            return $this->formatOrderConfirmation($order, $quote, $acceptances);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            $existing = BulkQuoteOrder::where('bulk_quote_request_id', $quote->id)->first();
            if ($existing) {
                return $this->formatOrderConfirmation($existing, $quote, $acceptances);
            }

            return response()->json([
                'status' => false,
                'message' => 'Failed to confirm the order.',
                'error' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Failed to confirm the order.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Group the accepted quote rows into one billing line per vendor + variant.
     *
     * The same variant can be present in more than one bulk-cart/quote row (for
     * example 20 units and 200 units). Vendors quote GST and delivery once per
     * variant, so those charges must be applied once per variant, not once per
     * accepted row.
     */
    protected function buildLockedBillingLines($acceptances, $quote): array
    {
        $itemsById = $quote->items->keyBy('id');

        // Fallback data for old acceptance rows that do not carry GST/delivery.
        $vendorItems = BulkQuoteRequestVendorItem::whereIn(
            'bulk_quote_request_vendor_id',
            $acceptances->pluck('bulk_quote_request_vendor_id')
        )
            ->whereIn(
                'bulk_quote_request_item_id',
                $acceptances->pluck('bulk_quote_request_item_id')
            )
            ->get()
            ->keyBy(function ($v) {
                return $v->bulk_quote_request_vendor_id . '_' . $v->bulk_quote_request_item_id;
            });

        $lines = [];

        foreach ($acceptances->groupBy('vendor_id') as $vendorId => $vendorAcceptances) {
            $variantGroups = $vendorAcceptances->groupBy(function ($acceptance) use ($itemsById) {
                $item = $itemsById->get($acceptance->bulk_quote_request_item_id);

                return $item ? (int) $item->variant_id : 0;
            });

            foreach ($variantGroups as $variantId => $variantAcceptances) {
                $firstAcceptance = $variantAcceptances->first();
                $firstItem = $itemsById->get($firstAcceptance->bulk_quote_request_item_id);
                $variant = $firstItem ? $firstItem->variant : null;
                $product = $variant ? $variant->product : null;

                $qty = 0;
                $baseAmount = 0.0;
                $gstAmount = 0.0;
                $deliveryCharges = 0.0;
                $originalAmount = 0.0;
                $gstPercentage = 0.0;
                $quotedUnitPrice = null;
                $itemIds = [];
                $acceptanceDetails = [];

                foreach ($variantAcceptances as $acceptance) {
                    $item = $itemsById->get($acceptance->bulk_quote_request_item_id);
                    $itemQty = $item ? (int) $item->qty : 0;

                    $viKey = $acceptance->bulk_quote_request_vendor_id . '_' . $acceptance->bulk_quote_request_item_id;
                    $vi = $vendorItems->get($viKey);

                    $itemGstPercentage = (float) $acceptance->gst_percentage;
                    $itemDelivery = (float) $acceptance->delivery_charges;

                    if ($itemGstPercentage == 0 && $itemDelivery == 0 && $vi) {
                        $itemGstPercentage = (float) $vi->gst_percentage;
                        $itemDelivery = (float) $vi->delivery_charges;
                    }

                    $base = (float) $acceptance->quoted_amount;
                    $itemGstAmt = round($base * ($itemGstPercentage / 100), 2);
                    $itemOriginalAmount = $this->itemOriginalAmount($item);

                    $qty += $itemQty;
                    $baseAmount += $base;
                    $gstAmount += $itemGstAmt;
                    // Delivery is quoted once per variant, so keep it once even
                    // when the variant is split across multiple accepted rows.
                    $deliveryCharges = max($deliveryCharges, $itemDelivery);
                    $originalAmount += $itemOriginalAmount;

                    if ($gstPercentage == 0.0 && $itemGstPercentage != 0.0) {
                        $gstPercentage = $itemGstPercentage;
                    }

                    if ($quotedUnitPrice === null && $acceptance->quoted_unit_price !== null) {
                        $quotedUnitPrice = (float) $acceptance->quoted_unit_price;
                    }

                    $itemIds[] = (int) $acceptance->bulk_quote_request_item_id;
                    $acceptanceDetails[] = [
                        'acceptance' => $acceptance,
                        'item' => $item,
                        'qty' => $itemQty,
                        'orderQty' => $item ? (int) $item->order_qty : 0,
                        'baseAmount' => round($base, 2),
                        'gstPercentage' => $itemGstPercentage,
                        'gstAmount' => $itemGstAmt,
                        'originalAmount' => round($itemOriginalAmount, 2),
                    ];
                }

                $lines[] = [
                    'vendorId' => (int) $vendorId,
                    'variantId' => (int) $variantId,
                    'itemIds' => $itemIds,
                    'firstAcceptance' => $firstAcceptance,
                    'variant' => $variant,
                    'product' => $product,
                    'qty' => $qty,
                    'quotedUnitPrice' => $quotedUnitPrice,
                    'baseAmount' => round($baseAmount, 2),
                    'gstPercentage' => $gstPercentage,
                    'gstAmount' => round($gstAmount, 2),
                    'deliveryCharges' => round($deliveryCharges, 2),
                    'originalAmount' => round($originalAmount, 2),
                    'acceptanceDetails' => $acceptanceDetails,
                ];
            }
        }

        return $lines;
    }

    protected function formatLockedItemsByVendor($acceptances, $quote): array
    {
        $lines = $this->buildLockedBillingLines($acceptances, $quote);
        $vendors = [];

        foreach (collect($lines)->groupBy('vendorId') as $vendorId => $vendorLines) {
            $firstLine = $vendorLines->first();
            $firstAcceptance = $firstLine ? $firstLine['firstAcceptance'] : null;
            $vendor = $firstAcceptance ? $firstAcceptance->vendor : null;
            $vendorQuote = $firstAcceptance ? $firstAcceptance->vendorQuote : null;

            $baseAmount = 0.0;
            $gstAmount = 0.0;
            $deliveryCharges = 0.0;
            $originalAmount = 0.0;
            $items = [];

            foreach ($vendorLines as $line) {
                $variant = $line['variant'];
                $product = $line['product'];

                $baseAmount += $line['baseAmount'];
                $gstAmount += $line['gstAmount'];
                $deliveryCharges += $line['deliveryCharges'];
                $originalAmount += $line['originalAmount'];

                // Put the single variant-level delivery charge on the first
                // locked row only; the remaining split rows carry zero delivery.
                $deliveryApplied = false;

                foreach ($line['acceptanceDetails'] as $detail) {
                    $acceptance = $detail['acceptance'];
                    $itemDelivery = $deliveryApplied ? 0.0 : (float) $line['deliveryCharges'];
                    $deliveryApplied = true;

                    $items[] = [
                        'itemId' => (int) $acceptance->bulk_quote_request_item_id,
                        'variantId' => (int) $line['variantId'],
                        'productName' => $product ? $product->name : 'Unknown Product',
                        'variantName' => $variant ? $variant->variant_name : 'Unknown Variant',
                        'brandName' => ($product && $product->brand) ? $product->brand->brand_name : null,
                        'categoryName' => ($product && $product->category) ? $product->category->name : null,
                        'qty' => $detail['qty'],
                        'orderQty' => $detail['orderQty'],
                        'unit' => $variant ? $variant->unit : 'Units',
                        'quotedUnitPrice' => (float) $acceptance->quoted_unit_price,
                        'quotedAmount' => $detail['baseAmount'],
                        'originalAmount' => $detail['originalAmount'],
                        'gstPercentage' => $detail['gstPercentage'],
                        'gstAmount' => $detail['gstAmount'],
                        'deliveryCharges' => round($itemDelivery, 2),
                        'itemTotalPayable' => round($detail['baseAmount'] + $detail['gstAmount'] + $itemDelivery, 2),
                        'status' => $acceptance->status,
                        'statusLabel' => $acceptance->status,
                        'image' => $this->quoteItemImageUrl($detail['item']),
                    ];
                }
            }

            $baseAmount = round($baseAmount, 2);
            $gstAmount = round($gstAmount, 2);
            $deliveryCharges = round($deliveryCharges, 2);
            $originalAmount = round($originalAmount, 2);
            $totalPayable = round($baseAmount + $gstAmount + $deliveryCharges, 2);

            $vendors[] = [
                'vendorId' => (int) $vendorId,
                'businessName' => $vendor ? $vendor->business_name : 'Unknown Vendor',
                'contactPerson' => $vendor ? $vendor->contact_person : null,
                'logo' => ($vendor && $vendor->company_logo)
                    ? asset('uploads/company_logos/' . $vendor->company_logo)
                    : null,
                'address' => $vendor ? $vendor->address : null,
                'deliveryEstimate' => $vendorQuote
                    ? $this->buildDeliveryEstimateLabel($vendorQuote->expected_delivery_date)
                    : $this->buildDeliveryEstimateLabel($quote->preferred_delivery_date),
                'expectedDeliveryDate' => ($vendorQuote && $vendorQuote->expected_delivery_date)
                    ? $vendorQuote->expected_delivery_date->format('d-m-Y')
                    : null,
                'baseAmount' => $baseAmount,
                'gstPercentage' => 0, // Deprecated at top level
                'gstAmount' => $gstAmount,
                'deliveryCharges' => $deliveryCharges,
                'originalAmount' => $originalAmount,
                'savings' => max(0, round($originalAmount - $baseAmount, 2)),
                'totalPayable' => $totalPayable,
                'itemsCount' => count($items),
                'items' => $items,
            ];
        }

        usort($vendors, function ($a, $b) {
            return $a['totalPayable'] <=> $b['totalPayable'];
        });

        return $vendors;
    }

    /**
     * Bill summary for the locked items.
     *
     * Accepted rows are grouped per vendor + variant before GST and delivery
     * are applied, so a variant split into 20 and 200 units is charged one GST
     * amount and one delivery charge instead of two.
     */
    protected function buildLockedBillSummary($acceptances, $quote): array
    {
        $lines = $this->buildLockedBillingLines($acceptances, $quote);

        $subTotal = 0.0;
        $gstTotal = 0.0;
        $deliveryTotal = 0.0;
        $originalTotal = 0.0;
        $totalQty = 0;

        $deliveryBreakDown = [];
        $gstBreakDown = [];

        foreach ($lines as $line) {
            $base = (float) $line['baseAmount'];
            $itemGstAmt = (float) $line['gstAmount'];
            $itemDelivery = (float) $line['deliveryCharges'];
            $gstPercentage = (float) $line['gstPercentage'];
            $productName = $line['product'] ? $line['product']->name : 'Unknown Product';
            $variantName = $line['variant'] ? $line['variant']->variant_name : null;

            $subTotal += $base;
            $gstTotal += $itemGstAmt;
            $deliveryTotal += $itemDelivery;
            $originalTotal += (float) $line['originalAmount'];
            $totalQty += (int) $line['qty'];

            $deliveryBreakDown[] = [
                'itemId' => $line['itemIds'][0] ?? null,
                'itemIds' => $line['itemIds'],
                'productName' => $productName,
                'variantName' => $variantName,
                'qty' => (int) $line['qty'],
                'amount' => $itemDelivery,
                'displayText' => "{$productName} (₹" . number_format($itemDelivery, 2) . ")",
            ];

            $gstBreakDown[] = [
                'itemId' => $line['itemIds'][0] ?? null,
                'itemIds' => $line['itemIds'],
                'productName' => $productName,
                'variantName' => $variantName,
                'qty' => (int) $line['qty'],
                'percentage' => $gstPercentage,
                'amount' => $itemGstAmt,
                'displayText' => "{$productName} ({$gstPercentage}% GST - ₹" . number_format($itemGstAmt, 2) . ")",
            ];
        }

        $subTotal = round($subTotal, 2);
        $gstTotal = round($gstTotal, 2);
        $deliveryTotal = round($deliveryTotal, 2);
        $originalTotal = round($originalTotal, 2);
        $totalPayable = round($subTotal + $gstTotal + $deliveryTotal, 2);

        return [
            'itemsCount' => $acceptances->count(),
            'totalQuantity' => $totalQty,
            'subTotalAmount' => $subTotal,
            'originalAmount' => $originalTotal,
            'bulkDiscount' => 0,
            'deliveryCharges' => $deliveryTotal,
            'deliveryChargesBreakDown' => $deliveryBreakDown,
            'taxesGst' => $gstTotal,
            'gstBreakDown' => $gstBreakDown,
            'totalSavings' => max(0, round($originalTotal - $subTotal, 2)),
            'totalPayable' => $totalPayable,
        ];
    }

    /**
     * Actual/base price of a quote item from product_variants for its ordered qty.
     */
    protected function itemOriginalAmount($item): float
    {
        if (!$item) {
            return 0.0;
        }

        $variant = $item->variant;

        if (!$variant) {
            return 0.0;
        }

        return (float) $variant->price * (int) $item->qty;
    }

    /**
     * Total actual/base price (MRP) for every item in a quote.
     */
    protected function calculateQuoteOriginalTotal($quote): float
    {
        return (float) $quote->items->sum(function ($item) {
            return $this->itemOriginalAmount($item);
        });
    }

    protected function formatBulkOrderSummary($order): array
    {
        $acceptances = BulkQuoteRequestItemAcceptance::with(['item.variant.product'])
            ->where('bulk_quote_request_id', $order->bulk_quote_request_id)
            ->get();

        $images = $acceptances
            ->map(fn($acceptance) => $this->quoteItemImageUrl($acceptance->item))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return [
            'orderId' => $order->id,
            'orderNumber' => $order->order_number,
            'transactionId' => $order->transaction_id,
            'status' => $order->status,
            'statusLabel' => $order->status,
            'amountPaid' => (float) $order->total_payable,
            'dateTime' => $order->confirmed_at
                ? Carbon::parse($order->confirmed_at)->format('d M, Y, g:i A')
                : null,
            'itemsCount' => $acceptances->count(),
            'image' => $images[0] ?? null,
            'images' => $images,
        ];
    }

    protected function formatOrderConfirmation($order, $quote, $acceptances)
    {
        return response()->json([
            'status' => true,
            'message' => 'Order confirmed successfully.',
            'data' => [
                'order' => $this->formatBulkOrderSummary($order),
                'quote' => $this->formatBulkQuoteHeader($quote, $this->calculateQuoteOriginalTotal($quote)),
                'vendors' => $this->formatLockedItemsByVendor($acceptances, $quote),
                'billSummary' => $this->buildLockedBillSummary($acceptances, $quote),
                'deliveryAddress' => [
                    'location' => $quote->delivery_location,
                    'label' => null,
                ],
            ]
        ], 200);
    }
}