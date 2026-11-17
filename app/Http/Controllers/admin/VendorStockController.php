<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Vendor;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\VendorStock;
use Carbon\Carbon;
use Exception;

class VendorStockController extends Controller
{
    public function manage_stock(Request $request)
    {
        $query = VendorStock::with(['vendor', 'product', 'variant'])->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('vendor', function ($vendorQuery) use ($search) {
                        $vendorQuery->where('business_name', 'like', "%{$search}%")
                            ->orWhere('business_mobile', 'like', "%{$search}%");
                    })
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        $stocks = $query->paginate(15);
        $vendors = Vendor::where('trash', 0)->where('status', 1)->get();

        return view('admin.inventory.manage_vendor_stock', compact('stocks', 'vendors'));
    }

    public function add_stock()
    {
        $vendors = Vendor::where('trash', 0)->where('status', 1)->get();
        return view('admin.inventory.add_vendor_stock', compact('vendors'));
    }

    public function get_variants_for_stockjok(Request $request)
    {
        if (!$request->filled('vendor_id')) {
            return response()->json(['success' => false, 'message' => 'Vendor ID is required.']);
        }

        $vendor = Vendor::find($request->vendor_id);

        if (!$vendor || empty($vendor->products_supplied)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $categories = $vendor->products_supplied;

        $query = ProductVariant::with(['product'])
            ->whereHas('product', function ($q) use ($categories) {
                $q->where('trash', 0)
                    ->where('status', 1)
                    ->whereIn('category_id', $categories);
            })
            ->where('trash', 0)
            ->where('status', 1);

        $variants = $query->get();

        return response()->json([
            'success' => true,
            'data' => $variants
        ]);
    }

    // AJAX API: Fetch variants based on the Vendor's assigned categories
    public function get_variants_for_stock(Request $request)
    {
        if (!$request->filled('vendor_id')) {
            return response()->json(['success' => false, 'message' => 'Vendor ID is required.']);
        }

        $vendor = Vendor::find($request->vendor_id);

        // Check if vendor exists and has supplied categories
        if (!$vendor || empty($vendor->products_supplied)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $categories = $vendor->products_supplied;

        // Added 'product.category' to eager load the category details
        $query = ProductVariant::with(['product', 'product.category'])
            ->whereHas('product', function ($q) use ($categories) {
                $q->where('trash', 0)
                    ->where('status', 1)
                    ->whereIn('category_id', $categories); // Filter by vendor's categories
            })
            ->where('trash', 0)
            ->where('status', 1);

        $variants = $query->get();

        return response()->json([
            'success' => true,
            'data' => $variants
        ]);
    }

    public function store_stock(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'stock_data' => 'required|array',
            'stock_data.*.quantity' => 'nullable|integer|min:1',
            'stock_data.*.batch_number' => 'nullable|string|max:100',
            // Update validation to accept DD-MM-YYYY
            'stock_data.*.received_date' => 'nullable|date_format:d-m-Y',
            'stock_data.*.expiry_date' => 'nullable|date_format:d-m-Y',
        ]);

        try {
            DB::beginTransaction();

            $vendor_id = $request->vendor_id;
            $admin_id = Auth::guard('admin')->id() ?? 1;
            $itemsProcessed = 0;

            foreach ($request->stock_data as $variant_id => $data) {
                if (empty($data['quantity']) || $data['quantity'] <= 0) {
                    continue;
                }

                $variant = ProductVariant::findOrFail($variant_id);

                // Parse DD-MM-YYYY to YYYY-MM-DD for database storage
                $receivedDate = !empty($data['received_date'])
                    ? Carbon::createFromFormat('d-m-Y', $data['received_date'])->format('Y-m-d')
                    : date('Y-m-d');

                $expiryDate = !empty($data['expiry_date'])
                    ? Carbon::createFromFormat('d-m-Y', $data['expiry_date'])->format('Y-m-d')
                    : null;

                VendorStock::create([
                    'vendor_id' => $vendor_id,
                    'product_variant_id' => $variant_id,
                    'product_id' => $variant->product_id,
                    'batch_number' => $data['batch_number'] ?? 'N/A',
                    'invoice_number' => $data['invoice_number'] ?? null,
                    'received_date' => $receivedDate,
                    'expiry_date' => $expiryDate,
                    'quantity' => $data['quantity'],
                    'purchase_price' => $data['purchase_price'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'added_by' => $admin_id,
                ]);

                $variant->stock += $data['quantity'];
                $variant->save();

                $product = Product::findOrFail($variant->product_id);
                $product->stock += $data['quantity'];
                $product->save();

                $itemsProcessed++;
            }

            if ($itemsProcessed === 0) {
                DB::rollBack();
                return back()->with('error', 'No stock quantities were entered. Process aborted.');
            }

            DB::commit();
            return redirect()->route('admin.vendor_stock.manage')->with('success', "Stock updated successfully for {$itemsProcessed} variant(s).");

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Stock update failed: ' . $e->getMessage());
        }
    }

    public function edit_stock($id)
    {
        $stock = VendorStock::with(['vendor', 'product', 'variant'])->findOrFail($id);
        return view('admin.inventory.edit_vendor_stock', compact('stock'));
    }

    public function update_stock(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'batch_number' => 'nullable|string|max:100',
            'invoice_number' => 'nullable|string|max:100',
            // Update validation to accept DD-MM-YYYY
            'received_date' => 'nullable|date_format:d-m-Y',
            'expiry_date' => 'nullable|date_format:d-m-Y',
        ]);

        try {
            DB::beginTransaction();

            $stock = VendorStock::findOrFail($id);
            $old_quantity = $stock->quantity;
            $new_quantity = $request->quantity;
            $difference = $new_quantity - $old_quantity;

            $variant = ProductVariant::find($stock->product_variant_id);
            if ($variant) {
                $variant->stock += $difference;
                if ($variant->stock < 0)
                    $variant->stock = 0;
                $variant->save();
            }

            $product = Product::find($stock->product_id);
            if ($product) {
                $product->stock += $difference;
                if ($product->stock < 0)
                    $product->stock = 0;
                $product->save();
            }

            // Parse DD-MM-YYYY to YYYY-MM-DD for database storage
            $receivedDate = !empty($request->received_date)
                ? Carbon::createFromFormat('d-m-Y', $request->received_date)->format('Y-m-d')
                : null;

            $expiryDate = !empty($request->expiry_date)
                ? Carbon::createFromFormat('d-m-Y', $request->expiry_date)->format('Y-m-d')
                : null;

            $stock->update([
                'quantity' => $new_quantity,
                'batch_number' => $request->batch_number ?? 'N/A',
                'invoice_number' => $request->invoice_number,
                'received_date' => $receivedDate,
                'expiry_date' => $expiryDate,
            ]);

            DB::commit();
            return redirect()->route('admin.vendor_stock.manage')->with('success', 'Stock entry updated and overall inventory adjusted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update stock: ' . $e->getMessage());
        }
    }

    public function delete_stock($id)
    {
        try {
            DB::beginTransaction();

            $stock = VendorStock::findOrFail($id);
            $quantity_to_remove = $stock->quantity;

            $variant = ProductVariant::find($stock->product_variant_id);
            if ($variant) {
                $variant->stock -= $quantity_to_remove;
                if ($variant->stock < 0)
                    $variant->stock = 0;
                $variant->save();
            }

            $product = Product::find($stock->product_id);
            if ($product) {
                $product->stock -= $quantity_to_remove;
                if ($product->stock < 0)
                    $product->stock = 0;
                $product->save();
            }

            $stock->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Stock entry deleted and overall inventory reverted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete stock: ' . $e->getMessage());
        }
    }
}