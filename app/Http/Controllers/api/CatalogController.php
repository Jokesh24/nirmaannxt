<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\ProductCategory;
use App\Models\ProductSubcategory;

class CatalogController extends Controller
{
    /**
     * 1. Get Master List of Brands
     */
    public function getBrands(Request $request)
    {
        $brands = Brand::where('status', 1)
            ->where('trash', 0)
            ->orderBy('brand_name', 'asc')
            ->get();

        $formattedBrands = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'brandName' => $brand->brand_name,
                'brandDescription' => $brand->brand_description,
                'logo' => $brand->logo ? asset('uploads/brands/' . $brand->logo) : null,
                // 'status' => (int) $brand->status
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Brands retrieved successfully.',
            'data' => [
                'brands' => $formattedBrands
            ]
        ], 200);
    }

    /**
     * 2. Get Product Categories (with isSubCategory flag)
     */
    public function getCategories(Request $request)
    {
        // Use withCount to efficiently calculate if subcategories exist in a single optimized query
        $categories = ProductCategory::where('status', 1)
            ->where('trash', 0)
            ->withCount([
                'subcategories' => function ($query) {
                    $query->where('status', 1)->where('trash', 0);
                }
            ])
            ->orderBy('display_order', 'asc')
            ->get();

        $formattedCategories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'displayOrder' => (int) $category->display_order,
                'image' => $category->image ? asset('uploads/category/' . $category->image) : null,
                // 'status' => (int) $category->status,
                // Determine if subcategories exist based on the relational count
                'isSubCategory' => $category->subcategories_count > 0 ? 1 : 0
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Categories retrieved successfully.',
            'data' => [
                'categories' => $formattedCategories
            ]
        ], 200);
    }

    /**
     * 3. Get Product Subcategories
     * Optionally accepts a 'categoryId' parameter to filter by a specific parent category.
     */
    public function getSubcategories(Request $request)
    {
        $query = ProductSubcategory::where('status', 1)
            ->where('trash', 0)
            ->orderBy('display_order', 'asc');

        // Optional filter: If the mobile app passes a categoryId, filter the results
        if ($request->has('categoryId')) {
            $query->where('category_id', $request->categoryId);
        }

        $subcategories = $query->get();

        $formattedSubcategories = $subcategories->map(function ($subcategory) {
            return [
                'id' => $subcategory->id,
                'categoryId' => (int) $subcategory->category_id,
                'name' => $subcategory->name,
                'displayOrder' => (int) $subcategory->display_order,
                'image' => $subcategory->image ? asset('uploads/subcategory/' . $subcategory->image) : null,
                // 'status' => (int) $subcategory->status
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Subcategories retrieved successfully.',
            'data' => [
                'subcategories' => $formattedSubcategories
            ]
        ], 200);
    }

    /**
     * 4. Search Brands
     * Accepts a 'q' or 'keyword' parameter to search brands by name.
     */
    public function searchBrands(Request $request)
    {
        $keyword = $request->get('q') ?? $request->get('keyword');

        $query = Brand::where('status', 1)
            ->where('trash', 0);

        if (!empty($keyword)) {
            $query->where('brand_name', 'like', '%' . $keyword . '%');
        }

        $brands = $query->orderBy('brand_name', 'asc')->get();

        $formattedBrands = $brands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'brandName' => $brand->brand_name,
                'brandDescription' => $brand->brand_description,
                'logo' => $brand->logo ? asset('uploads/brands/' . $brand->logo) : null,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Brands searched successfully.',
            'data' => [
                'brands' => $formattedBrands
            ]
        ], 200);
    }

    /**
     * 5. Search Product Categories (with isSubCategory flag)
     * Accepts a 'q' or 'keyword' parameter to search categories by name.
     */
    public function searchCategories(Request $request)
    {
        $keyword = $request->get('q') ?? $request->get('keyword');

        $query = ProductCategory::where('status', 1)
            ->where('trash', 0)
            ->withCount([
                'subcategories' => function ($q) {
                    $q->where('status', 1)->where('trash', 0);
                }
            ]);

        if (!empty($keyword)) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }

        $categories = $query->orderBy('display_order', 'asc')->get();

        $formattedCategories = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'displayOrder' => (int) $category->display_order,
                'image' => $category->image ? asset('uploads/category/' . $category->image) : null,
                'isSubCategory' => $category->subcategories_count > 0 ? 1 : 0
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Categories searched successfully.',
            'data' => [
                'categories' => $formattedCategories
            ]
        ], 200);
    }
}