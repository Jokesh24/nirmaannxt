<?php
namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductReason;
use App\Models\Brand;
use App\Models\Colour;
use App\Models\Size;
use File;

class ProductController extends Controller
{
    public function get_subcategories($category_id)
    {
        $subcategories = ProductSubcategory::where('category_id', $category_id)
            ->where('trash', 0)
            ->where('status', 1)
            ->get();

        return response()->json($subcategories);
    }

    public function add_product()
    {
        $brands = Brand::where('trash', 0)->where('status', 1)->get();
        $colours = Colour::where('trash', 0)->where('status', 1)->get();
        $sizes = Size::where('trash', 0)->where('status', 1)->get();
        $categories = ProductCategory::where('trash', 0)->where('status', 1)->get();
        $subcategories = ProductSubcategory::where('trash', 0)->where('status', 1)->get();

        return view('admin.productsandcategories.add_product', compact('categories', 'subcategories', 'brands', 'sizes', 'colours'));
    }

    public function store_product(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:product_categories,id',
            'subcategory_id' => 'nullable|exists:product_subcategories,id',
            'price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'instant_price' => 'nullable|numeric|min:0',
            'instant_offer_price' => 'nullable|numeric|min:0',
            'royalty_percentage' => 'nullable|numeric|min:0|max:100',
            'sla_days' => 'nullable|integer|min:0',
            'is_trending' => 'required|in:0,1',
            'qty' => 'nullable|numeric|min:0',
            'variant_qty.*' => 'nullable|numeric|min:0',
            'product_image' => 'required|image|max:4096',
            'variant_name.*' => 'nullable|string|max:255',
            'variant_price.*' => 'nullable|numeric|min:0',
            'reason_title.*' => 'nullable|string|max:255', // Validations for new section
            'reason_tagline.*' => 'nullable|string|max:255',
            'reason_icon.*' => 'nullable|image|max:2048',
        ]);

        return Product::resolveConnection()->transaction(function () use ($request) {

            // Step 1: Create Master Product
            $product = new Product();
            $product->name = $request->product_name;
            $product->brand_id = $request->brand_id;
            $product->category_id = $request->category_id;
            $product->subcategory_id = $request->subcategory_id;
            $product->sku = $request->sku;

            // Dual Pricing Mapping
            $product->price = $request->price;
            $product->offer_price = $request->offer_price;
            $product->instant_price = $request->instant_price;
            $product->instant_offer_price = $request->instant_offer_price;

            // Royalty Configuration
            $product->royalty_percentage = $request->royalty_percentage ?? 0.00;

            $product->sla_days = $request->sla_days;
            $product->unit = $request->unit;
            $product->qty = $request->qty; // New line
            $product->status = ($request->status == 'Active') ? 1 : 0;
            $product->description = $request->description;
            $product->is_trending = $request->is_trending;

            // System Default Clears
            $product->stock = 0;
            $product->best_before = null;
            $product->short_description = null;
            $product->is_featured = 0;
            $product->inventory_note = null;
            $product->trash = 0;

            if ($request->hasFile('product_image')) {
                $file = $request->file('product_image');
                $filename = time() . '_main_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/products'), $filename);
                $product->image = $filename;
            }

            if ($request->hasFile('gallery_images')) {
                $galleryPaths = [];
                foreach ($request->file('gallery_images') as $image) {
                    $gName = time() . '_gal_' . $image->getClientOriginalName();
                    $image->move(public_path('uploads/products/gallery'), $gName);
                    $galleryPaths[] = $gName;
                }
                $product->gallery = $galleryPaths;
            }
            $product->save();

            // Step 2: Create Primary Variant (Index #0)
            $firstVariant = new ProductVariant();
            $firstVariant->product_id = $product->id;
            $firstVariant->variant_name = ($request->has('variant_name') && !empty($request->variant_name[0]))
                ? $request->variant_name[0]
                : $product->name . ' - Standard Option';

            $firstVariant->sku = !empty($request->variant_sku[0]) ? $request->variant_sku[0] : $product->sku;
            $firstVariant->price = !empty($request->variant_price[0]) ? $request->variant_price[0] : $product->price;
            $firstVariant->offer_price = !empty($request->variant_offer_price[0]) ? $request->variant_offer_price[0] : $product->offer_price;
            $firstVariant->instant_price = !empty($request->variant_instant_price[0]) ? $request->variant_instant_price[0] : $product->instant_price;
            $firstVariant->instant_offer_price = !empty($request->variant_instant_offer_price[0]) ? $request->variant_instant_offer_price[0] : $product->instant_offer_price;
            $firstVariant->royalty_percentage = isset($request->variant_royalty_percentage[0]) && $request->variant_royalty_percentage[0] !== ''
                ? $request->variant_royalty_percentage[0]
                : $product->royalty_percentage;

            $firstVariant->unit = !empty($request->variant_unit[0]) ? $request->variant_unit[0] : $product->unit;
            // New line: Inherit master qty if variant qty is empty
            $firstVariant->qty = isset($request->variant_qty[0]) && $request->variant_qty[0] !== ''
                ? $request->variant_qty[0]
                : $product->qty;
            $firstVariant->status = $product->status;
            $firstVariant->description = $product->description;

            $firstVariant->stock = 0;
            $firstVariant->best_before = null;
            $firstVariant->short_description = null;
            $firstVariant->is_featured = 0;
            $firstVariant->is_trending = $product->is_trending;

            $firstVariant->image = $product->image;
            $firstVariant->gallery = $product->gallery;

            // Standard Attributes (Size & Color)
            $v1Attributes = [];
            if (!empty($request->variant_size[0])) {
                $v1Attributes['Size'] = $request->variant_size[0];
            }
            if (!empty($request->variant_color[0])) {
                $v1Attributes['Color'] = $request->variant_color[0];
            }

            $firstVariant->attributes = !empty($v1Attributes) ? $v1Attributes : null;
            $firstVariant->features = null;
            $firstVariant->save();

            // Step 3: Loop through secondary variant entries
            if ($request->has('variant_price')) {
                foreach ($request->variant_price as $key => $val) {
                    if ($key === 0) {
                        continue;
                    }

                    if (empty($val) && empty($request->variant_sku[$key])) {
                        continue;
                    }

                    $variant = new ProductVariant();
                    $variant->product_id = $product->id;
                    $variant->variant_name = $request->variant_name[$key] ?? ($product->name . ' Option #' . ($key + 1));
                    $variant->sku = $request->variant_sku[$key] ?? null;
                    $variant->price = $request->variant_price[$key];
                    $variant->offer_price = $request->variant_offer_price[$key] ?? null;
                    $variant->instant_price = $request->variant_instant_price[$key] ?? $product->instant_price;
                    $variant->instant_offer_price = $request->variant_instant_offer_price[$key] ?? $product->instant_offer_price;
                    $variant->royalty_percentage = isset($request->variant_royalty_percentage[$key]) && $request->variant_royalty_percentage[$key] !== ''
                        ? $request->variant_royalty_percentage[$key]
                        : $product->royalty_percentage;

                    $variant->unit = $request->variant_unit[$key] ?? null;
                    // New line: Inherit master qty if variant qty is empty
                    $variant->qty = isset($request->variant_qty[$key]) && $request->variant_qty[$key] !== ''
                        ? $request->variant_qty[$key]
                        : $product->qty;
                    $variant->status = ($request->variant_status[$key] == 'Active') ? 1 : 0;

                    $variant->stock = 0;
                    $variant->best_before = null;
                    $variant->short_description = null;
                    $variant->is_featured = 0;
                    $variant->is_trending = $product->is_trending;

                    $variant->description = $product->description;

                    $itemAttributes = [];
                    if (!empty($request->variant_size[$key])) {
                        $itemAttributes['Size'] = $request->variant_size[$key];
                    }
                    if (!empty($request->variant_color[$key])) {
                        $itemAttributes['Color'] = $request->variant_color[$key];
                    }

                    $variant->attributes = !empty($itemAttributes) ? $itemAttributes : null;
                    $variant->features = null;

                    if ($request->hasFile("variant_image.$key")) {
                        $file = $request->file("variant_image.$key");
                        $vname = time() . "_v_{$key}_" . $file->getClientOriginalName();
                        $file->move(public_path('uploads/products'), $vname);
                        $variant->image = $vname;
                    } else {
                        $variant->image = $product->image;
                    }

                    if ($request->hasFile("variant_gallery.$key")) {
                        $vGals = [];
                        foreach ($request->file("variant_gallery.$key") as $gFile) {
                            $vGName = time() . "_vgal_{$key}_" . $gFile->getClientOriginalName();
                            $gFile->move(public_path('uploads/products/gallery'), $vGName);
                            $vGals[] = $vGName;
                        }
                        $variant->gallery = $vGals;
                    } else {
                        $variant->gallery = $product->gallery;
                    }

                    $variant->save();
                }
            }

            // Step 4: Add 'Why you'll like it' Reasons
            if ($request->has('reason_title')) {
                File::ensureDirectoryExists(public_path('uploads/products/reasons'));

                foreach ($request->reason_title as $key => $title) {
                    if (empty($title))
                        continue;

                    $reason = new ProductReason();
                    $reason->product_id = $product->id;
                    $reason->title = $title;
                    $reason->tagline = $request->reason_tagline[$key] ?? null;

                    if ($request->hasFile("reason_icon.$key")) {
                        $file = $request->file("reason_icon.$key");
                        $filename = time() . '_icon_' . $key . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/products/reasons'), $filename);
                        $reason->icon = $filename;
                    }
                    $reason->save();
                }
            }

            return redirect()->back()->with('success', 'Product and Delivery Tiers configured successfully.');
        });
    }

    public function manage_product(Request $request)
    {
        $categories = ProductCategory::where('trash', 0)->get();
        $subcategories = ProductSubcategory::where('trash', 0)->get();
        $brands = Brand::where('trash', 0)->get();

        $subcategories_filtered = collect();
        if ($request->filled('category_id')) {
            $subcategories_filtered = ProductSubcategory::where('category_id', $request->category_id)
                ->where('trash', 0)
                ->get();
        }

        $query = Product::with(['category', 'subcategory', 'brand'])->where('trash', 0);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        if ($request->filled('status')) {
            $statusVal = ($request->status == 'Active') ? 1 : 0;
            $query->where('status', $statusVal);
        }

        $products = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.productsandcategories.manage_product', compact('products', 'brands', 'categories', 'subcategories', 'subcategories_filtered'));
    }

    public function product_view($id)
    {
        // Eager load relationships for optimized performance
        $product = Product::with(['category', 'subcategory', 'variants', 'reasons'])->findOrFail($id);
        return view('admin.productsandcategories.product_view', compact('product'));
    }

    public function delete_product($id)
    {
        $product = Product::findOrFail($id);
        $product->trash = 1;
        $product->save();
        return redirect()->back()->with('success', 'Product moved to trash.');
    }

    public function edit_product($id)
    {
        $product = Product::with('variants')->findOrFail($id);

        $brands = Brand::where('trash', 0)->where('status', 1)->get();
        $categories = ProductCategory::where('trash', 0)->get();
        $subcategories = $product->category_id ? ProductSubcategory::where('category_id', $product->category_id)->where('trash', 0)->get() : [];

        // Fetch static attributes for dropdowns
        $colours = Colour::where('trash', 0)->where('status', 1)->get();
        $sizes = Size::where('trash', 0)->where('status', 1)->get();

        return view('admin.productsandcategories.edit_product', compact('product', 'brands', 'categories', 'subcategories', 'colours', 'sizes'));
    }

    public function update_product(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'required|exists:product_categories,id',
            'subcategory_id' => 'nullable|exists:product_subcategories,id',
            'price' => 'required|numeric|min:0',
            'offer_price' => 'nullable|numeric|min:0',
            'instant_price' => 'nullable|numeric|min:0',
            'instant_offer_price' => 'nullable|numeric|min:0',
            'royalty_percentage' => 'nullable|numeric|min:0|max:100',
            'qty' => 'nullable|numeric|min:0',
            'sla_days' => 'nullable|integer|min:0',
            'is_trending' => 'required|in:0,1',
            'variant_name.*' => 'nullable|string|max:255',
            'variant_price.*' => 'nullable|numeric|min:0',
            'variant_qty.*' => 'nullable|numeric|min:0',
            'reason_title.*' => 'nullable|string|max:255',
            'reason_tagline.*' => 'nullable|string|max:255',
            'reason_icon.*' => 'nullable|image|max:2048',

        ]);

        return Product::resolveConnection()->transaction(function () use ($request, $id) {
            $product = Product::findOrFail($id);

            // Step 1: Update Master Product
            $product->name = $request->product_name;
            $product->brand_id = $request->brand_id;
            $product->category_id = $request->category_id;
            $product->subcategory_id = $request->subcategory_id;
            $product->sku = $request->sku;

            $product->price = $request->price;
            $product->offer_price = $request->offer_price;
            $product->instant_price = $request->instant_price;
            $product->instant_offer_price = $request->instant_offer_price;
            $product->royalty_percentage = $request->royalty_percentage ?? 0.00;

            $product->sla_days = $request->sla_days;
            $product->unit = $request->unit;
            $product->qty = $request->qty;
            $product->status = ($request->status == 'Active') ? 1 : 0;
            $product->description = $request->description;
            $product->is_trending = $request->is_trending;

            // Handle Master Image Update
            if ($request->hasFile('product_image')) {
                if ($product->image && \File::exists(public_path('uploads/products/' . $product->image))) {
                    \File::delete(public_path('uploads/products/' . $product->image));
                }
                $file = $request->file('product_image');
                $filename = time() . '_main_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/products'), $filename);
                $product->image = $filename;
            }

            // Handle Master Gallery Update
            if ($request->hasFile('gallery_images')) {
                if ($product->gallery) {
                    foreach ($product->gallery as $oldImg) {
                        if (\File::exists(public_path('uploads/products/gallery/' . $oldImg))) {
                            \File::delete(public_path('uploads/products/gallery/' . $oldImg));
                        }
                    }
                }
                $galleryPaths = [];
                foreach ($request->file('gallery_images') as $image) {
                    $gName = time() . '_gal_' . $image->getClientOriginalName();
                    $image->move(public_path('uploads/products/gallery'), $gName);
                    $galleryPaths[] = $gName;
                }
                $product->gallery = $galleryPaths;
            }
            $product->save();

            // Step 2: Synchronize Variants
            $submittedVariantIds = array_filter($request->variant_id ?? []);

            // Identify and drop removed variations
            $currentVariants = ProductVariant::where('product_id', $product->id)->get();
            foreach ($currentVariants as $oldVariant) {
                if (!in_array($oldVariant->id, $submittedVariantIds)) {
                    if ($oldVariant->image && $oldVariant->image !== $product->image && \File::exists(public_path('uploads/products/' . $oldVariant->image))) {
                        \File::delete(public_path('uploads/products/' . $oldVariant->image));
                    }
                    $oldVariant->delete();
                }
            }

            if ($request->has('variant_price')) {
                foreach ($request->variant_price as $key => $val) {
                    // Skip empty rows
                    if (empty($val) && empty($request->variant_sku[$key])) {
                        continue;
                    }

                    $vId = $request->variant_id[$key] ?? null;
                    $variant = $vId ? ProductVariant::find($vId) : new ProductVariant();

                    $variant->product_id = $product->id;
                    $variant->variant_name = $request->variant_name[$key] ?? ($product->name . ' Option #' . ($key + 1));
                    $variant->sku = $request->variant_sku[$key] ?? null;

                    $variant->price = $request->variant_price[$key];
                    $variant->offer_price = $request->variant_offer_price[$key] ?? null;
                    $variant->instant_price = $request->variant_instant_price[$key] ?? $product->instant_price;
                    $variant->instant_offer_price = $request->variant_instant_offer_price[$key] ?? $product->instant_offer_price;
                    $variant->royalty_percentage = isset($request->variant_royalty_percentage[$key]) && $request->variant_royalty_percentage[$key] !== ''
                        ? $request->variant_royalty_percentage[$key]
                        : $product->royalty_percentage;

                    $variant->unit = $request->variant_unit[$key] ?? null;
                    $variant->qty = isset($request->variant_qty[$key]) && $request->variant_qty[$key] !== ''
                        ? $request->variant_qty[$key]
                        : $product->qty;

                    $variant->status = ($request->variant_status[$key] == 'Active') ? 1 : 0;
                    $variant->description = $product->description;
                    $variant->is_trending = $product->is_trending;

                    // Handle Static Attributes (Size & Color)
                    $itemAttributes = [];
                    if (!empty($request->variant_size[$key])) {
                        $itemAttributes['Size'] = $request->variant_size[$key];
                    }
                    if (!empty($request->variant_color[$key])) {
                        $itemAttributes['Color'] = $request->variant_color[$key];
                    }
                    $variant->attributes = !empty($itemAttributes) ? $itemAttributes : null;

                    // Image Storage rules applied to update method
                    if ($key === 0) {
                        // Row #1 ALWAYS takes master image directly
                        $variant->image = $product->image;
                        $variant->gallery = $product->gallery;
                    } else {
                        // Custom variant images
                        if ($request->hasFile("variant_image.$key")) {
                            if ($variant->image && $variant->image !== $product->image && \File::exists(public_path('uploads/products/' . $variant->image))) {
                                \File::delete(public_path('uploads/products/' . $variant->image));
                            }
                            $vFile = $request->file("variant_image.$key");
                            $vName = time() . "_v_{$key}_" . $vFile->getClientOriginalName();
                            $vFile->move(public_path('uploads/products'), $vName);
                            $variant->image = $vName;
                        } elseif (!$variant->image) {
                            $variant->image = $product->image;
                        }

                        if ($request->hasFile("variant_gallery.$key")) {
                            if ($variant->gallery && $variant->gallery !== $product->gallery) {
                                foreach ($variant->gallery as $oldVGal) {
                                    if (\File::exists(public_path('uploads/products/gallery/' . $oldVGal))) {
                                        \File::delete(public_path('uploads/products/gallery/' . $oldVGal));
                                    }
                                }
                            }
                            $vgPaths = [];
                            foreach ($request->file("variant_gallery.$key") as $vgFile) {
                                $vgName = time() . "_vgal_{$key}_" . $vgFile->getClientOriginalName();
                                $vgFile->move(public_path('uploads/products/gallery'), $vgName);
                                $vgPaths[] = $vgName;
                            }
                            $variant->gallery = $vgPaths;
                        } elseif (!$variant->gallery) {
                            $variant->gallery = $product->gallery;
                        }
                    }

                    $variant->save();
                }
            }

            // Step 3: Synchronize "Why You'll Like It" Reasons
            $submittedReasonIds = array_filter($request->reason_id ?? []);
            $currentReasons = ProductReason::where('product_id', $product->id)->get();
            foreach ($currentReasons as $oldReason) {
                if (!in_array($oldReason->id, $submittedReasonIds)) {
                    if ($oldReason->icon && \File::exists(public_path('uploads/products/reasons/' . $oldReason->icon))) {
                        \File::delete(public_path('uploads/products/reasons/' . $oldReason->icon));
                    }
                    $oldReason->delete();
                }
            }

            if ($request->has('reason_title')) {
                File::ensureDirectoryExists(public_path('uploads/products/reasons'));

                foreach ($request->reason_title as $key => $title) {
                    if (empty($title))
                        continue;

                    $rId = $request->reason_id[$key] ?? null;
                    $reason = $rId ? ProductReason::find($rId) : new ProductReason();

                    $reason->product_id = $product->id;
                    $reason->title = $title;
                    $reason->tagline = $request->reason_tagline[$key] ?? null;

                    if ($request->hasFile("reason_icon.$key")) {
                        if ($reason->icon && \File::exists(public_path('uploads/products/reasons/' . $reason->icon))) {
                            \File::delete(public_path('uploads/products/reasons/' . $reason->icon));
                        }
                        $file = $request->file("reason_icon.$key");
                        $filename = time() . '_icon_' . $key . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/products/reasons'), $filename);
                        $reason->icon = $filename;
                    }
                    $reason->save();
                }
            }

            return redirect()->route('admin.products.manage')->with('success', 'Product updated successfully.');
        });
    }

    public function order_list()
    {
        return view('admin.productsandcategories.order_list');
    }

    public function add_product_category()
    {
        return view('admin.productsandcategories.add_product_category');
    }

    public function store_product_categoryold(Request $request)
    {
        $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                'unique:product_categories,name',
                'regex:/^[a-zA-Z\s]+$/u',
            ],
            'display_order' => 'nullable|integer|min:0',
            'status' => 'required|in:Active,Inactive',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ], [
            'category_name.regex' => 'The category name must only contain letters and spaces. Numbers are not allowed.',
        ]);

        try {
            $category = new ProductCategory();
            $category->name = $request->category_name;
            $category->display_order = ($request->display_order >= 0) ? $request->display_order : 0;
            $category->description = $request->description;
            $category->status = ($request->status == 'Active') ? 1 : 0;
            $category->trash = 0;

            if ($request->hasFile('category_image')) {
                $file = $request->file('category_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/category'), $filename);
                $category->image = $filename;
            }

            $category->save();
            return redirect()->back()->with('success', 'Category added successfully!');


        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function store_product_category(Request $request)
    {
        $request->validate([
            'category_name' => [
                'required',
                'string',
                'max:255',
                'unique:product_categories,name',
                'regex:/^[a-zA-Z\s]+$/u',
            ],
            'display_order' => 'nullable|integer|min:0',
            'status' => 'required|in:Active,Inactive',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string',
        ], [
            'category_name.regex' => 'The category name must only contain letters and spaces. Numbers are not allowed.',
        ]);

        try {
            $category = new ProductCategory();
            $category->name = $request->category_name;
            $category->display_order = ($request->display_order >= 0) ? $request->display_order : 0;
            $category->description = $request->description;
            $category->status = ($request->status == 'Active') ? 1 : 0;
            $category->trash = 0;

            if ($request->hasFile('category_image')) {
                $file = $request->file('category_image');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/category'), $filename);
                $category->image = $filename;
            }

            $category->save();

            // --- NEW LOGIC FOR AJAX ---
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Category added successfully!',
                    'data' => [
                        'id' => $category->id,
                        'name' => $category->name
                    ]
                ]);
            }

            return redirect()->back()->with('success', 'Category added successfully!');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->back()->withInput()->with('error', 'Something went wrong. Please try again.');
        }
    }

    public function manage_product_category(Request $request)
    {
        $query = ProductCategory::where('trash', 0);

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('status') && $request->status != '') {
            $statusVal = ($request->status == 'Active') ? 1 : 0;
            $query->where('status', $statusVal);
        }

        $categories = $query->orderBy('display_order', 'asc')->paginate(10);
        return view('admin.productsandcategories.manage_product_categories', compact('categories'));
    }

    public function edit_product_category($id)
    {
        $category = ProductCategory::findOrFail($id);
        return view('admin.productsandcategories.edit_product_category', compact('category'));
    }

    public function update_product_category(Request $request, $id)
    {
        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4096',
        ]);

        $category = ProductCategory::findOrFail($id);
        $category->name = $request->category_name;
        $category->display_order = $request->display_order ?? 0;
        $category->description = $request->description;
        $category->status = ($request->status == 'Active') ? 1 : 0;

        if ($request->hasFile('category_image')) {
            if ($category->image && File::exists(public_path('uploads/category/' . $category->image))) {
                File::delete(public_path('uploads/category/' . $category->image));
            }

            $file = $request->file('category_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/category'), $filename);
            $category->image = $filename;
        }

        $category->save();
        return redirect()->route('admin.product_categories.manage')->with('success', 'Category updated successfully!');
    }

    public function delete_product_category($id)
    {
        $category = ProductCategory::findOrFail($id);
        $category->trash = 1;
        $category->save();
        return redirect()->back()->with('success', 'Category moved to trash.');
    }

    public function add_product_subcategory()
    {
        $categories = ProductCategory::where('trash', 0)->where('status', 1)->get();
        return view('admin.productsandcategories.add_product_subcategory', compact('categories'));
    }

    public function store_product_subcategory(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:product_categories,id',
            'subcategory_name.*' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/u'
            ],
            'display_order.*' => 'nullable|integer|min:0',
            'status.*' => 'required|in:Active,Inactive',
            'subcategory_image.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'subcategory_name.*.regex' => 'Each subcategory name must only contain letters and spaces.'
        ]);

        $names = $request->subcategory_name;
        $orders = $request->display_order;
        $statuses = $request->status;
        $images = $request->file('subcategory_image');

        try {
            ProductSubcategory::resolveConnection()->transaction(function () use ($request, $names, $orders, $statuses, $images) {
                foreach ($names as $key => $name) {
                    $subcategory = new ProductSubcategory();
                    $subcategory->category_id = $request->category_id;
                    $subcategory->name = $name;

                    $orderVal = $orders[$key] ?? 0;
                    $subcategory->display_order = ($orderVal >= 0) ? (int) $orderVal : 0;
                    $subcategory->status = ($statuses[$key] == 'Active') ? 1 : 0;
                    $subcategory->trash = 0;

                    if (isset($images[$key])) {
                        $file = $images[$key];
                        $filename = time() . '_' . $key . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/subcategory'), $filename);
                        $subcategory->image = $filename;
                    }
                    $subcategory->save();
                }
            });

            return redirect()->back()->with('success', count($names) . ' Subcategories added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Critical Error: ' . $e->getMessage());
        }
    }

    public function manage_product_subcategory(Request $request)
    {
        $categories = ProductCategory::where('trash', 0)->get();
        $query = ProductSubcategory::with('category')->where('trash', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $statusVal = ($request->status == 'Active') ? 1 : 0;
            $query->where('status', $statusVal);
        }

        $subcategories = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.productsandcategories.manage_product_subcategories', compact('subcategories', 'categories'));
    }

    public function edit_product_subcategory($id)
    {
        $subcategory = ProductSubcategory::findOrFail($id);
        $categories = ProductCategory::where('trash', 0)->get();
        return view('admin.productsandcategories.edit_product_subcategory', compact('subcategory', 'categories'));
    }

    public function update_product_subcategory(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required',
            'subcategory_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z\s]+$/u'
            ],
            'subcategory_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'subcategory_name.regex' => 'The subcategory name must only contain letters and spaces.'
        ]);

        $subcategory = ProductSubcategory::findOrFail($id);
        $subcategory->category_id = $request->category_id;
        $subcategory->name = $request->subcategory_name;
        $subcategory->display_order = $request->display_order ?? 0;
        $subcategory->status = ($request->status == 'Active') ? 1 : 0;

        if ($request->hasFile('subcategory_image')) {
            if ($subcategory->image && File::exists(public_path('uploads/subcategory/' . $subcategory->image))) {
                File::delete(public_path('uploads/subcategory/' . $subcategory->image));
            }

            $file = $request->file('subcategory_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/subcategory'), $filename);
            $subcategory->image = $filename;
        }

        $subcategory->save();
        return redirect()->route('admin.product_subcategories.manage')->with('success', 'Subcategory updated successfully!');
    }

    public function delete_product_subcategory($id)
    {
        $subcategory = ProductSubcategory::findOrFail($id);
        $subcategory->trash = 1;
        $subcategory->save();
        return redirect()->back()->with('success', 'Subcategory moved to trash.');
    }

}