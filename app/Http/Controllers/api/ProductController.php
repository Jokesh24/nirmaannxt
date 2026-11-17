<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\RecentSearch;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    public function getProductsold(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make($request->all(), [
            // Multiple IDs supported
            'brandId' => 'sometimes|nullable|array',
            'brandId.*' => 'integer|exists:brands,id',

            'categoryId' => 'sometimes|nullable|array',
            'categoryId.*' => 'integer|exists:product_categories,id',

            'subCategoryId' => 'sometimes|nullable|array',
            'subCategoryId.*' => 'integer|exists:product_subcategories,id',

            // Sorting
            'sort' => 'sometimes|nullable|string|in:price_asc,price_desc,popularity,rating,delivery_time',

            // Price
            'priceRange' => 'sometimes|nullable|string',

            // Other filters
            'availability' => 'sometimes|nullable|array',
            'deliveryTime' => 'sometimes|nullable|array',
            'colors' => 'sometimes|nullable|array',
            'rating' => 'sometimes|nullable|numeric|in:1,2,3,4,5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Clean Request Values
        |--------------------------------------------------------------------------
        */

        // Convert IDs into clean arrays
        $brandIds = collect($request->input('brandId', []))
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()->values()->toArray();

        $categoryIds = collect($request->input('categoryId', []))
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()->values()->toArray();

        $subCategoryIds = collect($request->input('subCategoryId', []))
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()->values()->toArray();

        $colors = collect($request->input('colors', []))
            ->filter(function ($color) {
                return !is_null($color) && trim($color) !== '';
            })
            ->map(function ($color) {
                return strtolower(trim($color));
            })
            ->unique()->values()->toArray();

        $availability = collect($request->input('availability', []))
            ->filter(function ($value) {
                return !is_null($value) && trim($value) !== '';
            })
            ->map(function ($value) {
                return strtolower(trim($value));
            })
            ->unique()->values()->toArray();

        $deliveryTime = collect($request->input('deliveryTime', []))
            ->filter(function ($value) {
                return !is_null($value) && trim($value) !== '';
            })
            ->map(function ($value) {
                return strtolower(trim($value));
            })
            ->unique()->values()->toArray();

        $priceRange = trim((string) $request->input('priceRange', ''));

        $hasVariantFilter = !empty($colors) || !empty($availability) || !empty($deliveryTime) || $priceRange !== '';

        /*
        |--------------------------------------------------------------------------
        | 3. Shared Variant Filter Closure
        |--------------------------------------------------------------------------
        | This closure ensures that both `whereHas` (to filter parent products)
        | and `with` (to eager load matching variants) apply exactly the same rules.
        */
        $variantFilterClosure = function ($q) use ($colors, $availability, $deliveryTime, $priceRange) {
            $q->where('status', 1)->where('trash', 0);

            // COLOR
            if (!empty($colors)) {
                $q->where(function ($subQ) use ($colors) {
                    foreach ($colors as $color) {
                        $subQ->orWhereRaw('LOWER(attributes) LIKE ?', ['%"color":"' . $color . '"%']);
                        $subQ->orWhereRaw('LOWER(attributes) LIKE ?', ['%"color": "' . $color . '"%']);
                    }
                });
            }

            // PRICE
            if ($priceRange !== '') {
                $range = explode('-', $priceRange);
                if (count($range) === 2) {
                    $min = (float) trim($range[0]);
                    $max = (float) trim($range[1]);

                    $sellingPriceSql = 'COALESCE(NULLIF(offer_price, 0), price)';
                    $q->whereRaw("$sellingPriceSql >= ?", [$min])
                        ->whereRaw("$sellingPriceSql <= ?", [$max]);
                }
            }

            // AVAILABILITY (Instant Delivery)
            if (!empty($availability)) {
                if (in_array('instant_delivery', $availability)) {
                    $q->where('instant_price', '>', 0);
                }
            }
        };

        /*
        |--------------------------------------------------------------------------
        | 4. Base Product Query
        |--------------------------------------------------------------------------
        */
        $query = Product::query()
            ->with([
                'brand',
                'subcategory',
                'variants' => $variantFilterClosure
            ])
            ->where('status', 1)
            ->where('trash', 0);

        // BRAND FILTER
        if (!empty($brandIds)) {
            $query->whereIn('brand_id', $brandIds);
        }

        // CATEGORY FILTER
        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        // SUB CATEGORY FILTER
        if (!empty($subCategoryIds)) {
            $query->whereIn('subcategory_id', $subCategoryIds);
        }

        // VARIANT FILTERS (Ensure the parent product actually has matching variants)
        if ($hasVariantFilter) {
            $query->whereHas('variants', $variantFilterClosure);
        }

        // PRODUCT LEVEL DELIVERY TIME FILTER
        // (Since SLA days are stored on the Product model in your DB, not the Variant)
        if (!empty($deliveryTime)) {
            $query->where(function ($deliveryQuery) use ($deliveryTime) {
                $conditions = [];
                if (in_array('24_hours', $deliveryTime))
                    $conditions[] = 1;
                if (in_array('3_days', $deliveryTime))
                    $conditions[] = 3;
                if (in_array('7_days', $deliveryTime))
                    $conditions[] = 7;

                if (!empty($conditions)) {
                    $deliveryQuery->where('sla_days', '<=', max($conditions));
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 5. PRODUCT RATING FILTER
        |--------------------------------------------------------------------------
        */
        if ($request->filled('rating')) {
            $minRating = (float) $request->rating;
            $query->where(function ($q) use ($minRating) {
                $q->selectRaw('COALESCE(AVG(rating), 0)')
                    ->from('product_ratings')
                    ->whereColumn('product_ratings.product_id', 'products.id')
                    ->limit(1);
            }, '>=', $minRating);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. SORTING
        |--------------------------------------------------------------------------
        */
        $sort = $request->input('sort');
        $sellingPriceSql = 'COALESCE(NULLIF(offer_price, 0), price)';

        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw("$sellingPriceSql ASC");
                break;
            case 'price_desc':
                $query->orderByRaw("$sellingPriceSql DESC");
                break;
            case 'delivery_time':
                $query->orderBy('sla_days', 'asc');
                break;
            case 'popularity':
                $query->orderBy('is_trending', 'desc')->orderBy('id', 'desc');
                break;
            case 'rating':
                $query->orderBy('id', 'desc'); // Optional: Order by actual rating aggregate
                break;
            default:
                $query->orderBy('id', 'desc');
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Execute Query & Format
        |--------------------------------------------------------------------------
        */
        $products = $query->get();

        // Fetch actual ratings and review counts for each variant
        $variantIds = $products->pluck('variants')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->values()
            ->toArray();

        $variantRatings = DB::table('product_ratings')
            ->select(
                'variant_id',
                DB::raw('ROUND(AVG(rating), 1) as average_rating'),
                DB::raw('COUNT(*) as total_reviews')
            )
            ->whereIn('variant_id', $variantIds)
            ->groupBy('variant_id')
            ->get()
            ->keyBy('variant_id');

        // $formattedProducts = $products->map(function ($product) {
        $formattedProducts = $products->map(function ($product) use ($variantRatings) {

            // If the product was returned but has absolutely no active/matching variants, skip it.
            if ($product->variants->isEmpty()) {
                return null;
            }

            $discountPercent = 0;
            $price = (float) $product->price;
            $offerPrice = (float) $product->offer_price;

            if ($price > 0 && $offerPrice > 0 && $price > $offerPrice) {
                $discountPercent = round((($price - $offerPrice) / $price) * 100);
            }

            $deliveryDateString = null;
            if ($product->sla_days) {
                $deliveryDateString = \Carbon\Carbon::now()->addDays($product->sla_days)->format('d M, Y');
            }

            $tag = $product->subcategory ? $product->subcategory->name : 'Standard Grade';

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
                'tag' => $tag,
                'rating' => 4.5,
                'isFavorite' => false,
                'qty' => (float) $product->qty,
                'unit' => $product->unit,
                'price' => $price,
                'offerPrice' => $offerPrice > 0 ? $offerPrice : $price,
                'discountPercentage' => $discountPercent,
                'slaDays' => (int) $product->sla_days,
                'deliveryDate' => $deliveryDateString,

                'variants' => $product->variants->map(function ($variant) use ($variantRatings) {

                    $variantRating = $variantRatings->get($variant->id);

                    $averageRating = $variantRating
                        ? (float) $variantRating->average_rating
                        : 0;

                    $totalReviews = $variantRating
                        ? (int) $variantRating->total_reviews
                        : 0;
                    $variantGalleryUrls = [];
                    if (is_array($variant->gallery)) {
                        foreach ($variant->gallery as $vImg) {
                            $variantGalleryUrls[] = asset('uploads/products/gallery/' . $vImg);
                        }
                    }

                    $variantQty = (float) $variant->qty > 0 ? (float) $variant->qty : 1;
                    $pricePerUnit = (float) $variant->price / $variantQty;
                    $offerPricePerUnit = (float) $variant->offer_price > 0 ? ((float) $variant->offer_price / $variantQty) : null;

                    return [
                        'id' => $variant->id,
                        'variantName' => $variant->variant_name,
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'offerPrice' => (float) $variant->offer_price,
                        'perUnitPrice' => round($pricePerUnit, 2),
                        'perUnitOfferPrice' => $offerPricePerUnit !== null ? round($offerPricePerUnit, 2) : null,
                        'instantPrice' => (float) $variant->instant_price,
                        'instantOfferPrice' => (float) $variant->instant_offer_price,
                        'royaltyPercentage' => (float) $variant->royalty_percentage,
                        'qty' => (float) $variant->qty,
                        'unit' => $variant->unit,
                        'stock' => (int) $variant->stock,
                        'attributes' => $variant->attributes,
                        'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                        'gallery' => $variantGalleryUrls,
                        'rating' => $averageRating,
                        'totalReviews' => $totalReviews,
                    ];
                })->values(),
            ];
        })->filter()->values();

        return response()->json([
            'status' => true,
            'message' => 'Product list retrieved successfully.',
            'data' => [
                'itemsFound' => $formattedProducts->count(),
                'products' => $formattedProducts,
            ]
        ], 200);
    }

    public function getProducts(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Validation
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make($request->all(), [
            // Multiple IDs supported
            'brandId' => 'sometimes|nullable|array',
            'brandId.*' => 'integer|exists:brands,id',

            'categoryId' => 'sometimes|nullable|array',
            'categoryId.*' => 'integer|exists:product_categories,id',

            'subCategoryId' => 'sometimes|nullable|array',
            'subCategoryId.*' => 'integer|exists:product_subcategories,id',

            // Sorting
            'sort' => 'sometimes|nullable|string|in:price_asc,price_desc,popularity,rating,delivery_time',

            // Price
            'priceRange' => 'sometimes|nullable|string',

            // Other filters
            'availability' => 'sometimes|nullable|array',
            'deliveryTime' => 'sometimes|nullable|array',
            'colors' => 'sometimes|nullable|array',
            'rating' => 'sometimes|nullable|numeric|in:1,2,3,4,5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Clean Request Values
        |--------------------------------------------------------------------------
        */

        // Convert IDs into clean arrays
        $brandIds = collect($request->input('brandId', []))
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()->values()->toArray();

        $categoryIds = collect($request->input('categoryId', []))
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()->values()->toArray();

        $subCategoryIds = collect($request->input('subCategoryId', []))
            ->filter(function ($id) {
                return $id !== null && $id !== '';
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()->values()->toArray();

        $colors = collect($request->input('colors', []))
            ->filter(function ($color) {
                return !is_null($color) && trim($color) !== '';
            })
            ->map(function ($color) {
                return strtolower(trim($color));
            })
            ->unique()->values()->toArray();

        $availability = collect($request->input('availability', []))
            ->filter(function ($value) {
                return !is_null($value) && trim($value) !== '';
            })
            ->map(function ($value) {
                return strtolower(trim($value));
            })
            ->unique()->values()->toArray();

        $deliveryTime = collect($request->input('deliveryTime', []))
            ->filter(function ($value) {
                return !is_null($value) && trim($value) !== '';
            })
            ->map(function ($value) {
                return strtolower(trim($value));
            })
            ->unique()->values()->toArray();

        $priceRange = trim((string) $request->input('priceRange', ''));

        $hasVariantFilter = !empty($colors) || !empty($availability) || !empty($deliveryTime) || $priceRange !== '';

        /*
        |--------------------------------------------------------------------------
        | 3. Shared Variant Filter Closure
        |--------------------------------------------------------------------------
        | This closure ensures that both `whereHas` (to filter parent products)
        | and `with` (to eager load matching variants) apply exactly the same rules.
        */
        $variantFilterClosure = function ($q) use ($colors, $availability, $deliveryTime, $priceRange) {
            $q->where('status', 1)->where('trash', 0);

            // COLOR
            if (!empty($colors)) {
                $q->where(function ($subQ) use ($colors) {
                    foreach ($colors as $color) {
                        $subQ->orWhereRaw('LOWER(attributes) LIKE ?', ['%"color":"' . $color . '"%']);
                        $subQ->orWhereRaw('LOWER(attributes) LIKE ?', ['%"color": "' . $color . '"%']);
                    }
                });
            }

            // PRICE
            if ($priceRange !== '') {
                $range = explode('-', $priceRange);
                if (count($range) === 2) {
                    $min = (float) trim($range[0]);
                    $max = (float) trim($range[1]);

                    $sellingPriceSql = 'COALESCE(NULLIF(offer_price, 0), price)';
                    $q->whereRaw("$sellingPriceSql >= ?", [$min])
                        ->whereRaw("$sellingPriceSql <= ?", [$max]);
                }
            }

            // AVAILABILITY (Instant Delivery)
            if (!empty($availability)) {
                if (in_array('instant_delivery', $availability)) {
                    $q->where('instant_price', '>', 0);
                }
            }
        };

        /*
        |--------------------------------------------------------------------------
        | 4. Base Product Query
        |--------------------------------------------------------------------------
        */
        $query = Product::query()
            ->with([
                'brand',
                'subcategory',
                'variants' => $variantFilterClosure
            ])
            ->where('status', 1)
            ->where('trash', 0);

        // BRAND FILTER
        if (!empty($brandIds)) {
            $query->whereIn('brand_id', $brandIds);
        }

        // CATEGORY FILTER
        if (!empty($categoryIds)) {
            $query->whereIn('category_id', $categoryIds);
        }

        // SUB CATEGORY FILTER
        if (!empty($subCategoryIds)) {
            $query->whereIn('subcategory_id', $subCategoryIds);
        }

        // VARIANT FILTERS (Ensure the parent product actually has matching variants)
        if ($hasVariantFilter) {
            $query->whereHas('variants', $variantFilterClosure);
        }

        // PRODUCT LEVEL DELIVERY TIME FILTER
        // (Since SLA days are stored on the Product model in your DB, not the Variant)
        if (!empty($deliveryTime)) {
            $query->where(function ($deliveryQuery) use ($deliveryTime) {
                $conditions = [];
                if (in_array('24_hours', $deliveryTime))
                    $conditions[] = 1;
                if (in_array('3_days', $deliveryTime))
                    $conditions[] = 3;
                if (in_array('7_days', $deliveryTime))
                    $conditions[] = 7;

                if (!empty($conditions)) {
                    $deliveryQuery->where('sla_days', '<=', max($conditions));
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 5. PRODUCT RATING FILTER
        |--------------------------------------------------------------------------
        */
        if ($request->filled('rating')) {
            $minRating = (float) $request->rating;
            $query->where(function ($q) use ($minRating) {
                $q->selectRaw('COALESCE(AVG(rating), 0)')
                    ->from('product_ratings')
                    ->whereColumn('product_ratings.product_id', 'products.id')
                    ->limit(1);
            }, '>=', $minRating);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. SORTING (Updated to sort by Variant Prices using Correlated Subqueries)
        |--------------------------------------------------------------------------
        */
        $sort = $request->input('sort');

        switch ($sort) {
            case 'price_asc':
                // Find the MIN price among variants for this specific product
                $sortQueryAsc = DB::table('product_variants')
                    ->selectRaw('MIN(COALESCE(NULLIF(offer_price, 0), price))')
                    ->whereColumn('product_variants.product_id', 'products.id');
                
                // Apply same variant filters so sorting respects colors/availability selections
                $variantFilterClosure($sortQueryAsc);
                
                $query->orderByRaw("({$sortQueryAsc->toSql()}) ASC", $sortQueryAsc->getBindings());
                break;

            case 'price_desc':
                // Find the MAX price among variants for this specific product
                $sortQueryDesc = DB::table('product_variants')
                    ->selectRaw('MAX(COALESCE(NULLIF(offer_price, 0), price))')
                    ->whereColumn('product_variants.product_id', 'products.id');
                
                // Apply same variant filters
                $variantFilterClosure($sortQueryDesc);
                
                $query->orderByRaw("({$sortQueryDesc->toSql()}) DESC", $sortQueryDesc->getBindings());
                break;

            case 'delivery_time':
                $query->orderBy('sla_days', 'asc');
                break;

            case 'popularity':
                $query->orderBy('is_trending', 'desc')->orderBy('id', 'desc');
                break;

            case 'rating':
                $query->orderBy('id', 'desc'); // Optional: Order by actual rating aggregate
                break;

            default:
                $query->orderBy('id', 'desc');
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Execute Query & Format
        |--------------------------------------------------------------------------
        */
        $products = $query->get();

        // Fetch actual ratings and review counts for each variant
        $variantIds = $products->pluck('variants')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->values()
            ->toArray();

        $variantRatings = DB::table('product_ratings')
            ->select(
                'variant_id',
                DB::raw('ROUND(AVG(rating), 1) as average_rating'),
                DB::raw('COUNT(*) as total_reviews')
            )
            ->whereIn('variant_id', $variantIds)
            ->groupBy('variant_id')
            ->get()
            ->keyBy('variant_id');

        $formattedProducts = $products->map(function ($product) use ($variantRatings) {

            // If the product was returned but has absolutely no active/matching variants, skip it.
            if ($product->variants->isEmpty()) {
                return null;
            }

            $discountPercent = 0;
            $price = (float) $product->price;
            $offerPrice = (float) $product->offer_price;

            if ($price > 0 && $offerPrice > 0 && $price > $offerPrice) {
                $discountPercent = round((($price - $offerPrice) / $price) * 100);
            }

            $deliveryDateString = null;
            if ($product->sla_days) {
                $deliveryDateString = \Carbon\Carbon::now()->addDays($product->sla_days)->format('d M, Y');
            }

            $tag = $product->subcategory ? $product->subcategory->name : 'Standard Grade';

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
                'tag' => $tag,
                'rating' => 4.5,
                'isFavorite' => false,
                'qty' => (float) $product->qty,
                'unit' => $product->unit,
                'price' => $price,
                'offerPrice' => $offerPrice > 0 ? $offerPrice : $price,
                'discountPercentage' => $discountPercent,
                'slaDays' => (int) $product->sla_days,
                'deliveryDate' => $deliveryDateString,

                'variants' => $product->variants->map(function ($variant) use ($variantRatings) {

                    $variantRating = $variantRatings->get($variant->id);

                    $averageRating = $variantRating
                        ? (float) $variantRating->average_rating
                        : 0;

                    $totalReviews = $variantRating
                        ? (int) $variantRating->total_reviews
                        : 0;
                    $variantGalleryUrls = [];
                    if (is_array($variant->gallery)) {
                        foreach ($variant->gallery as $vImg) {
                            $variantGalleryUrls[] = asset('uploads/products/gallery/' . $vImg);
                        }
                    }

                    $variantQty = (float) $variant->qty > 0 ? (float) $variant->qty : 1;
                    $pricePerUnit = (float) $variant->price / $variantQty;
                    $offerPricePerUnit = (float) $variant->offer_price > 0 ? ((float) $variant->offer_price / $variantQty) : null;

                    return [
                        'id' => $variant->id,
                        'variantName' => $variant->variant_name,
                        'sku' => $variant->sku,
                        'price' => (float) $variant->price,
                        'offerPrice' => (float) $variant->offer_price,
                        'perUnitPrice' => round($pricePerUnit, 2),
                        'perUnitOfferPrice' => $offerPricePerUnit !== null ? round($offerPricePerUnit, 2) : null,
                        'instantPrice' => (float) $variant->instant_price,
                        'instantOfferPrice' => (float) $variant->instant_offer_price,
                        'royaltyPercentage' => (float) $variant->royalty_percentage,
                        'qty' => (float) $variant->qty,
                        'unit' => $variant->unit,
                        'stock' => (int) $variant->stock,
                        'attributes' => $variant->attributes,
                        'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                        'gallery' => $variantGalleryUrls,
                        'rating' => $averageRating,
                        'totalReviews' => $totalReviews,
                    ];
                })->values(),
            ];
        })->filter()->values();

        return response()->json([
            'status' => true,
            'message' => 'Product list retrieved successfully.',
            'data' => [
                'itemsFound' => $formattedProducts->count(),
                'products' => $formattedProducts,
            ]
        ], 200);
    }

    public function getProductDetails(Request $request)
    {
        // 1. Validate the incoming request payload 
        $validator = Validator::make($request->all(), [
            'productId' => 'required|integer|exists:products,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $id = $request->productId;

        // 2. Eager load all heavy relations for just ONE product
        $product = Product::with([
            'brand',
            'reasons',
            'variants' => function ($q) {
                $q->where('status', 1)->where('trash', 0);
            }
        ])
            ->where('id', $id)
            ->where('status', 1)
            ->where('trash', 0)
            ->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found or unavailable.'
            ], 404);
        }

        // Fetch actual ratings and review counts for each variant
        $variantIds = $product->variants->pluck('id')->toArray();

        $variantRatings = DB::table('product_ratings')
            ->select(
                'variant_id',
                DB::raw('ROUND(AVG(rating), 1) as average_rating'),
                DB::raw('COUNT(*) as total_reviews')
            )
            ->whereIn('variant_id', $variantIds)
            ->groupBy('variant_id')
            ->get()
            ->keyBy('variant_id');

        // 3. Fetch Cart Quantities if the user is authenticated
        // This ensures the API works for both guests (0 qty) and logged-in users
        // Fixed: Using the standard auth()->user() instead of explicitly targeting the sanctum guard
        $user = auth()->user();
        $cartQuantities = [];
        $totalProductCartQty = 0;

        if ($user) {
            $variantIds = $product->variants->pluck('id');
            $cartItems = \App\Models\Cart::where('user_id', $user->id)
                ->whereIn('variant_id', $variantIds)
                ->get();

            foreach ($cartItems as $item) {
                $cartQuantities[$item->variant_id] = $item->qty;
                $totalProductCartQty += $item->qty;
            }
        }

        // 4. Format Master Gallery URLs
        $galleryUrls = [];
        if (is_array($product->gallery)) {
            foreach ($product->gallery as $img) {
                $galleryUrls[] = asset('uploads/products/gallery/' . $img);
            }
        }

        // 5. Map the payload
        $formattedProduct = [
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
            'totalCartQty' => (float) $totalProductCartQty,
            'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
            'gallery' => $galleryUrls,

            // Format 'Why You'll Like It' reasons
            'reasons' => $product->reasons->map(function ($reason) {
                return [
                    'id' => $reason->id,
                    'title' => $reason->title,
                    'tagline' => $reason->tagline,
                    'icon' => $reason->icon ? asset('uploads/products/reasons/' . $reason->icon) : null,
                ];
            })->values(),

            // Format variants
            'variants' => $product->variants->map(function ($variant) use ($cartQuantities, $variantRatings) {

                $variantRating = $variantRatings->get($variant->id);

                $averageRating = $variantRating
                    ? (float) $variantRating->average_rating
                    : 0;

                $totalReviews = $variantRating
                    ? (int) $variantRating->total_reviews
                    : 0;
                $variantGalleryUrls = [];
                if (is_array($variant->gallery)) {
                    foreach ($variant->gallery as $vImg) {
                        $variantGalleryUrls[] = asset('uploads/products/gallery/' . $vImg);
                    }
                }

                // Inject the specific cart quantity for this variant
                $variantCartQty = isset($cartQuantities[$variant->id]) ? (float) $cartQuantities[$variant->id] : 0;

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
                    'cartQty' => $variantCartQty,
                    'attributes' => $variant->attributes,
                    'image' => $variant->image ? asset('uploads/products/' . $variant->image) : null,
                    'gallery' => $variantGalleryUrls,
                    'rating' => $averageRating,
                    'totalReviews' => $totalReviews,
                ];
            })->values()
        ];

        return response()->json([
            'status' => true,
            'message' => 'Product details retrieved successfully.',
            'data' => [
                'product' => $formattedProduct
            ]
        ], 200);
    }

    public function searchProductsold(Request $request)
    {
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

        // Use the 'api' guard to check for authenticated users
        $userId = Auth::guard('api')->id() ?? Auth::id();

        if ($userId) {
            RecentSearch::updateOrCreate(
                [
                    'user_id' => $userId,
                    'keyword' => $keyword
                ],
                [
                    'updated_at' => now()
                ]
            );
        }

        $words = explode(' ', $keyword);

        $query = Product::with([
            'brand',
            'category',
            'subcategory',
            'variants' => function ($q) {
                $q->where('status', 1)->where('trash', 0);
            }
        ])
            ->where('status', 1)
            ->where('trash', 0);

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

        $formattedProducts = $products->map(function ($product) {
            $discountPercent = 0;
            $price = (float) $product->price;
            $offerPrice = (float) $product->offer_price;

            if ($price > 0 && $offerPrice > 0 && $price > $offerPrice) {
                $discountPercent = round((($price - $offerPrice) / $price) * 100);
            }

            $deliveryDateString = null;
            if ($product->sla_days) {
                $deliveryDateString = \Carbon\Carbon::now()
                    ->addDays($product->sla_days)
                    ->format('d M, Y');
            }

            $tag = $product->subcategory ? $product->subcategory->name : 'Standard Grade';

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
                'tag' => $tag,
                'rating' => 4.5,
                'isFavorite' => false,
                'qty' => (float) $product->qty,
                'unit' => $product->unit,
                'price' => $price,
                'offerPrice' => $offerPrice > 0 ? $offerPrice : $price,
                'discountPercentage' => $discountPercent,
                'slaDays' => (int) $product->sla_days,
                'deliveryDate' => $deliveryDateString,

                'variants' => $product->variants->map(function ($variant) {
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

        // Extract related brands
        $relatedBrands = $products->pluck('brand')->filter()->unique('id')->values()->map(function ($brand) {
            return [
                'brandId' => $brand->id,
                'brandName' => $brand->brand_name,
                'brandLogo' => $brand->logo ? asset('uploads/brands/' . $brand->logo) : null,
            ];
        });

        // Extract related categories
        $relatedCategories = $products->pluck('category')->filter()->unique('id')->values()->map(function ($category) {
            return [
                'categoryId' => $category->id,
                'categoryName' => $category->name,
                // Include category image/icon if your categories table has one, e.g.:
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

    public function searchProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'keyword' => 'required|string|min:2|max:100',
            'categoryId' => 'nullable|array',
            'categoryId.*' => 'integer|exists:product_categories,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $keyword = trim(preg_replace('/\s+/', ' ', $request->keyword));
        $categoryId = $request->categoryId;

        // Use the 'api' guard to check for authenticated users
        $userId = Auth::guard('api')->id() ?? Auth::id();

        if ($userId) {
            RecentSearch::updateOrCreate(
                [
                    'user_id' => $userId,
                    'keyword' => $keyword
                ],
                [
                    'updated_at' => now()
                ]
            );
        }

        $words = explode(' ', $keyword);

        $query = Product::with([
            'brand',
            'category',
            'subcategory',
            'variants' => function ($q) {
                $q->where('status', 1)->where('trash', 0);
            }
        ])
            ->where('status', 1)
            ->where('trash', 0);

        // If categoryId is provided, filter by it
        // if (!empty($categoryId)) {
        //     $query->where('category_id', $categoryId);
        // }

        if (!empty($categoryId)) {
            $query->whereIn('category_id', $categoryId);
        }

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

        $formattedProducts = $products->map(function ($product) {
            $discountPercent = 0;
            $price = (float) $product->price;
            $offerPrice = (float) $product->offer_price;

            if ($price > 0 && $offerPrice > 0 && $price > $offerPrice) {
                $discountPercent = round((($price - $offerPrice) / $price) * 100);
            }

            $deliveryDateString = null;
            if ($product->sla_days) {
                $deliveryDateString = \Carbon\Carbon::now()
                    ->addDays($product->sla_days)
                    ->format('d M, Y');
            }

            $tag = $product->subcategory ? $product->subcategory->name : 'Standard Grade';

            return [
                'id' => $product->id,
                'name' => $product->name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
                'tag' => $tag,
                'rating' => 4.5,
                'isFavorite' => false,
                'qty' => (float) $product->qty,
                'unit' => $product->unit,
                'price' => $price,
                'offerPrice' => $offerPrice > 0 ? $offerPrice : $price,
                'discountPercentage' => $discountPercent,
                'slaDays' => (int) $product->sla_days,
                'deliveryDate' => $deliveryDateString,

                'variants' => $product->variants->map(function ($variant) {
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

        // Extract related brands
        $relatedBrands = $products->pluck('brand')->filter()->unique('id')->values()->map(function ($brand) {
            return [
                'brandId' => $brand->id,
                'brandName' => $brand->brand_name,
                'brandLogo' => $brand->logo ? asset('uploads/brands/' . $brand->logo) : null,
            ];
        });

        // Extract related categories
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

    public function getRecentSearches(Request $request)
    {
        // Use 'api' guard
        $userId = Auth::guard('api')->id() ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. User ID is required.',
                'data' => ['recentSearches' => []]
            ], 401);
        }

        $recentSearches = RecentSearch::with(['product.subcategory'])
            ->where('user_id', $userId)
            ->whereNotNull('product_id')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        $formattedRecent = $recentSearches->map(function ($search) {
            $product = $search->product;

            if (!$product)
                return null;

            $tag = $product->subcategory ? $product->subcategory->name : 'Standard Grade';

            return [
                'id' => $product->id,
                'name' => $product->name,
                'Price' => (float) $product->price,
                'offerPrice' => (float) $product->offer_price,
                'tag' => $tag,
                'image' => $product->image ? asset('uploads/products/' . $product->image) : null,
            ];
        })->filter()->values();

        return response()->json([
            'status' => true,
            'message' => 'Recent searches retrieved successfully.',
            'data' => [
                'recentSearches' => $formattedRecent
            ]
        ], 200);
    }

    public function logRecentSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'productId' => 'required|integer|exists:products,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Use 'api' guard
        $userId = Auth::guard('api')->id() ?? Auth::id();

        if ($userId) {
            RecentSearch::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $request->productId
                ],
                [
                    'updated_at' => now()
                ]
            );
        }

        return response()->json([
            'status' => true,
            'message' => 'Search logged successfully.'
        ], 200);
    }

    public function clearRecentSearches(Request $request)
    {
        // Use 'api' guard
        $userId = Auth::guard('api')->id() ?? Auth::id();

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. User ID is required.',
            ], 401);
        }

        RecentSearch::where('user_id', $userId)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Recent searches cleared successfully.',
        ], 200);
    }

    public function getFilterOptions(Request $request)
    {
        // 1. Build a base query for active products
        $productQuery = \App\Models\Product::where('status', 1)->where('trash', 0);

        // 2. Context Scoping: Apply request filters to fetch only relevant combinations
        if ($request->filled('categoryId')) {
            $productQuery->where('category_id', $request->categoryId);
        }
        if ($request->filled('brandId')) {
            $productQuery->where('brand_id', $request->brandId);
        }
        if ($request->filled('subCategoryId')) {
            $productQuery->where('subcategory_id', $request->subCategoryId);
        }

        // Keep a clone of the filtered query to extract IDs
        $filteredProductIds = clone $productQuery->pluck('id');

        // 3. Extract unique, active IDs currently in use by the filtered products
        $availableBrandIds = (clone $productQuery)->distinct()->pluck('brand_id')->filter();
        $availableCategoryIds = (clone $productQuery)->distinct()->pluck('category_id')->filter();
        $availableSubCategoryIds = (clone $productQuery)->distinct()->pluck('subcategory_id')->filter();

        // 4. Fetch ONLY the names of the associated active parent tables
        $brands = \App\Models\Brand::whereIn('id', $availableBrandIds)
            ->where('status', 1)->where('trash', 0)
            ->get(['id', 'brand_name']);

        $categories = \App\Models\ProductCategory::whereIn('id', $availableCategoryIds)
            ->where('status', 1)->where('trash', 0)
            ->get(['id', 'name']);

        $subCategories = \App\Models\ProductSubcategory::whereIn('id', $availableSubCategoryIds)
            ->where('status', 1)->where('trash', 0)
            ->get(['id', 'name']);

        // 5. Build the Dynamic Filter Array
        $filters = [];

        // A. Dynamic Brands
        if ($brands->isNotEmpty()) {
            $filters[] = [
                'filterKey' => 'brandId',
                'filterName' => 'Brand',
                'type' => 'checkbox',
                'options' => $brands->map(function ($brand) {
                    return ['id' => (string) $brand->id, 'label' => $brand->brand_name];
                })->values()
            ];
        }

        // B. Dynamic Price Ranges (with smart rounding)
        $rawMinPrice = (float) (clone $productQuery)->min(\Illuminate\Support\Facades\DB::raw('COALESCE(NULLIF(offer_price, 0), price)'));
        $rawMaxPrice = (float) (clone $productQuery)->max(\Illuminate\Support\Facades\DB::raw('COALESCE(NULLIF(offer_price, 0), price)'));

        $priceOptions = [];
        if ($rawMaxPrice > $rawMinPrice) {
            $diff = $rawMaxPrice - $rawMinPrice;
            $factor = $diff > 10000 ? 1000 : ($diff > 1000 ? 500 : ($diff > 100 ? 100 : ($diff > 50 ? 50 : ($diff > 10 ? 10 : 1))));

            $roundedMin = floor($rawMinPrice / $factor) * $factor;
            $roundedMax = ceil($rawMaxPrice / $factor) * $factor;

            $step = ceil(($roundedMax - $roundedMin) / 4 / $factor) * $factor;
            $step = max($factor, $step);

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
            $filters[] = [
                'filterKey' => 'priceRange',
                'filterName' => 'Price',
                'type' => 'radio',
                'options' => $priceOptions
            ];
        }

        // C. Dynamic Subcategories (Grades)
        if ($subCategories->isNotEmpty()) {
            $filters[] = [
                'filterKey' => 'subCategoryId',
                'filterName' => 'Grade',
                'type' => 'checkbox',
                'options' => $subCategories->map(function ($sub) {
                    return ['id' => (string) $sub->id, 'label' => $sub->name];
                })->values()
            ];
        }

        // D. Dynamic Categories (Material Types)
        if ($categories->isNotEmpty()) {
            $filters[] = [
                'filterKey' => 'categoryId',
                'filterName' => 'Material Type',
                'type' => 'checkbox',
                'options' => $categories->map(function ($cat) {
                    return ['id' => (string) $cat->id, 'label' => $cat->name];
                })->values()
            ];
        }

        // E. Dynamic Colours (Extract from JSON attributes)
        $variantsAttributes = \App\Models\ProductVariant::whereIn('product_id', $filteredProductIds)
            ->where('status', 1)->where('trash', 0)
            ->whereNotNull('attributes')
            ->pluck('attributes');

        $availableColors = [];
        foreach ($variantsAttributes as $attr) {
            $attrData = is_string($attr) ? json_decode($attr, true) : $attr;
            if (is_array($attrData) && !empty($attrData['Color'])) {
                $availableColors[] = trim($attrData['Color']);
            }
        }
        $availableColors = array_values(array_unique($availableColors));

        if (!empty($availableColors)) {
            $colorOptions = array_map(function ($color) {
                return ['id' => $color, 'label' => $color];
            }, $availableColors);

            $filters[] = [
                'filterKey' => 'colors',
                'filterName' => 'Colour',
                'type' => 'checkbox',
                'options' => $colorOptions
            ];
        }

        // F. Ratings Filter
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

        // G. Availability (Instant Delivery Check)
        $hasInstantDelivery = (clone $productQuery)->where(function ($q) {
            $q->where('instant_price', '>', 0)
                ->orWhereHas('variants', function ($vq) {
                    $vq->where('instant_price', '>', 0)->where('status', 1)->where('trash', 0);
                });
        })->exists();

        if ($hasInstantDelivery) {
            $filters[] = [
                'filterKey' => 'availability',
                'filterName' => 'Availability',
                'type' => 'checkbox',
                'options' => [
                    ['id' => 'instant_delivery', 'label' => 'Instant Delivery Available']
                ]
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Filter options retrieved successfully.',
            'data' => [
                'filters' => $filters
            ]
        ], 200);
    }

    public function getSortOptions(Request $request)
    {
        // The exact labels from the provided UI mockup
        $sortOptions = [
            [
                'key' => 'price_asc',
                'label' => 'Price- Low to High',
                'default' => true
            ],
            [
                'key' => 'price_desc',
                'label' => 'Price- High to Low',
                'default' => false
            ],
            [
                'key' => 'popularity',
                'label' => 'Most Popular',
                'default' => false
            ],
            [
                'key' => 'rating',
                'label' => 'Rating',
                'default' => false
            ],
            [
                'key' => 'delivery_time',
                'label' => 'Delivery time',
                'default' => false
            ]
        ];

        return response()->json([
            'status' => true,
            'message' => 'Sort options retrieved successfully.',
            'data' => [
                'sortOptions' => $sortOptions
            ]
        ], 200);
    }
}