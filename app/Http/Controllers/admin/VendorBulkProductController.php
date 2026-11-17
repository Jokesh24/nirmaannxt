<?php
namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\VendorBulkProduct;
use App\Models\Vendor;
use App\Models\ProductCategory;
use DB;

class VendorBulkProductController extends Controller
{
    // 1. Manage Page: Grouped by Vendor
    public function manage(Request $request)
    {
        $query = Vendor::withCount([
            'bulkProducts' => function ($q) {
                $q->where('status', 1)->where('trash', 0);
            }
        ])->where('trash', 0);

        if ($request->filled('search')) {
            $query->where('business_name', 'like', '\%' . $request->search . '%')
                ->orWhere('contact_person', 'like', '%' . $request->search . '%');
        }

        $vendors = $query->orderBy('id', 'desc')->paginate(15);

        return view('admin.vendor_bulk_products.manage', compact('vendors'));
    }

    // 2. Edit/Configure Page: Loads all eligible products for a specific vendor
    public function edit($vendor_id)
    {
        $vendor = Vendor::findOrFail($vendor_id);

        // Ensure products_supplied is an array of Category IDs
        $supplied_cat_ids = is_string($vendor->products_supplied)
            ? json_decode($vendor->products_supplied, true)
            : $vendor->products_supplied;

        if (empty($supplied_cat_ids)) {
            $supplied_cat_ids = []; // Fallback if none selected
        }

        // Fetch Categories -> Products -> Variants 
        $categories = ProductCategory::whereIn('id', $supplied_cat_ids)
            ->where('status', 1)->where('trash', 0)
            ->with([
                'products' => function ($q) {
                    $q->where('status', 1)->where('trash', 0)
                        ->with([
                            'variants' => function ($vq) {
                                $vq->where('status', 1)->where('trash', 0);
                            }
                        ]);
                }
            ])->get();

        // Get currently assigned variant IDs (Active)
        $assigned_variants = VendorBulkProduct::where('vendor_id', $vendor_id)
            ->where('status', 1)
            ->where('trash', 0)
            ->pluck('variant_id')
            ->toArray();

        return view('admin.vendor_bulk_products.edit', compact('vendor', 'categories', 'assigned_variants'));
    }

    // 3. Update Function: Saves all toggle states in one go
    public function update(Request $request, $vendor_id)
    {
        // Flatten values since variant_ids can be submitted as [product_id => variant_id]
        $raw_variants = $request->variant_ids ?? [];
        $selected_variants = array_values(array_filter((array) $raw_variants));
        $admin_id = Auth::id() ?? 1;

        try {
            DB::transaction(function () use ($vendor_id, $selected_variants, $admin_id) {

                // Step 1: Mark all existing active mappings for this vendor as inactive
                VendorBulkProduct::where('vendor_id', $vendor_id)
                    ->update(['status' => 0, 'updated_by' => $admin_id]);

                // Step 2: Loop through checked variants and set to active (or create new)
                foreach ($selected_variants as $variant_id) {
                    $mapping = VendorBulkProduct::where('vendor_id', $vendor_id)
                        ->where('variant_id', $variant_id)
                        ->first();

                    if ($mapping) {
                        $mapping->update([
                            'status' => 1,
                            'trash' => 0,
                            'updated_by' => $admin_id
                        ]);
                    } else {
                        VendorBulkProduct::create([
                            'vendor_id' => $vendor_id,
                            'variant_id' => $variant_id,
                            'status' => 1,
                            'trash' => 0,
                            'created_by' => $admin_id,
                            'createdby_role' => 'ADMIN',
                            'updated_by' => $admin_id,
                            'updatedby_role' => 'ADMIN'
                        ]);
                    }
                }
            });

            return redirect()->route('admin.vendor_bulk.manage')->with('success', 'Bulk order eligibility updated successfully for the vendor.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error saving mappings: ' . $e->getMessage());
        }
    }
}