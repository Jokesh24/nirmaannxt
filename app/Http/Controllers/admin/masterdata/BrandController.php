<?php

namespace App\Http\Controllers\admin\masterdata;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use Exception;

class BrandController extends Controller
{
    public function addBrand()
    {
        return view('admin.masterdata.brand.add_brand');
    }

    public function storeBrand(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name',
            'brand_description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required'
        ]);

        try {
            // Handle logo upload
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $image = $request->file('logo');
                $logoPath = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/brands'), $logoPath);
            }

            $brand = Brand::create([
                'brand_name' => $request->brand_name,
                'brand_description' => $request->brand_description,
                'logo' => $logoPath,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            // Check if the request is an AJAX request
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'id' => $brand->id,
                    'name' => $brand->brand_name
                ]);
            }

            // Default behavior for standard form submission
            return redirect()->route('manage_brand')->with('success', 'Brand added successfully!');

        } catch (Exception $e) {
            // Handle AJAX error
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            // Handle standard error
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function manageBrand(Request $request)
    {
        $query = Brand::where('trash', 0);

        // Search functionality
        if ($request->filled('search')) {
            $query->where('brand_name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status == 'Active' ? 1 : 0);
        }

        $brands = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.masterdata.brand.manage_brand', compact('brands'));
    }

    public function updateBrand(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $request->brand_id,
            'brand_description' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required'
        ]);

        try {
            $brand = Brand::findOrFail($request->brand_id);
            $logoPath = $brand->logo;

            // Handle new logo upload if provided
            if ($request->hasFile('logo')) {
                // Delete old logo file if it exists
                if ($logoPath && file_exists(public_path('uploads/brands/' . $logoPath))) {
                    unlink(public_path('uploads/brands/' . $logoPath));
                }

                $image = $request->file('logo');
                $logoPath = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/brands'), $logoPath);
            }

            $brand->update([
                'brand_name' => $request->brand_name,
                'brand_description' => $request->brand_description,
                'logo' => $logoPath,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            return redirect()->route('manage_brand')->with('success', 'Brand updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteBrand($id)
    {
        Brand::where('id', $id)->update(['trash' => 1]);
        return redirect()->route('manage_brand')->with('success', 'Brand deleted successfully!');
    }
}