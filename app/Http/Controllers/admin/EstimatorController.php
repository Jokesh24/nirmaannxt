<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EstimatorProject;
use App\Models\EstimatorMaterial;
use App\Models\EstimatorMeasurementUnit;
use App\Models\EstimatorProjectUnit;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class EstimatorController extends Controller
{
    public function index(Request $request)
    {
        // Eager load variant and its parent product
        $query = EstimatorProject::with('materials.variant.product')->where('trash', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        $projects = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.estimator.index', compact('projects'));
    }

    public function create()
    {
        $categories = ProductCategory::where('status', 1)->where('trash', 0)->get();
        $areaUnits = EstimatorMeasurementUnit::where('type', 'area')->where('status', 1)->get();
        $thicknessUnits = EstimatorMeasurementUnit::where('type', 'thickness')->where('status', 1)->get();

        return view('admin.estimator.form', compact('categories', 'areaUnits', 'thicknessUnits'));
    }

    public function edit($id)
    {
        $project = EstimatorProject::with(['materials', 'projectUnits'])->findOrFail($id);
        $categories = ProductCategory::where('status', 1)->where('trash', 0)->get();

        $areaUnits = EstimatorMeasurementUnit::where('type', 'area')->where('status', 1)->get();
        $thicknessUnits = EstimatorMeasurementUnit::where('type', 'thickness')->where('status', 1)->get();
        $projectUnitValues = $project->projectUnits->pluck('unit_value', 'unit_id')->toArray();

        // Eager load VARIANTS for the edit dropdowns
        foreach ($project->materials as $material) {
            $variants = ProductVariant::with('product:id,name')
                ->whereHas('product', function ($q) use ($material) {
                    $q->where('category_id', $material->category_id);
                })
                ->where('status', 1)->where('trash', 0)->get();

            $material->category_variants = $variants->map(function ($v) {
                return [
                    'id' => $v->id,
                    'name' => ($v->product->name ?? 'Product') . ' - ' . $v->variant_name
                ];
            });
        }

        return view('admin.estimator.form', compact('project', 'categories', 'areaUnits', 'thicknessUnits', 'projectUnitValues'));
    }

    public function store(Request $request)
    {
        // 1. Dynamic Validation Logic
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:area,bhk',
            'status' => 'required|in:0,1',
            'materials' => 'required|array|min:1',
            'materials.*.category_id' => 'required|exists:product_categories,id',
            'materials.*.variant_id' => 'required|exists:product_variants,id', // Validating against variants table
            'materials.*.base_quantity' => 'required|numeric|min:0',
            'materials.*.display_unit' => 'required|string|max:50',
        ];

        // Only require units if the strategy is 'area'
        if ($request->type === 'area') {
            $rules['area_units'] = 'required|array';
            $rules['area_units.*'] = 'required|numeric|min:0';
            $rules['thickness_units'] = 'required|array';
            $rules['thickness_units.*'] = 'required|numeric|min:0';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request) {
            $project = EstimatorProject::create([
                'type' => $request->type,
                'name' => $request->name,
                'status' => $request->status,
                'trash' => 0
            ]);

            if ($request->type === 'area') {
                if ($request->has('area_units')) {
                    foreach ($request->area_units as $unit_id => $value) {
                        EstimatorProjectUnit::create(['project_id' => $project->id, 'unit_id' => $unit_id, 'unit_value' => $value]);
                    }
                }
                if ($request->has('thickness_units')) {
                    foreach ($request->thickness_units as $unit_id => $value) {
                        EstimatorProjectUnit::create(['project_id' => $project->id, 'unit_id' => $unit_id, 'unit_value' => $value]);
                    }
                }
            }

            foreach ($request->materials as $mat) {
                EstimatorMaterial::create([
                    'project_id' => $project->id,
                    'category_id' => $mat['category_id'],
                    'variant_id' => $mat['variant_id'], // Saving variant_id
                    'base_quantity' => $mat['base_quantity'],
                    'display_unit' => $mat['display_unit']
                ]);
            }
        });

        return redirect()->route('admin.estimator.index')->with('success', 'Estimator rule configured successfully.');
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'type' => 'required|in:area,bhk',
            'status' => 'required|in:0,1',
            'materials' => 'required|array|min:1',
            'materials.*.category_id' => 'required|exists:product_categories,id',
            'materials.*.variant_id' => 'required|exists:product_variants,id',
            'materials.*.base_quantity' => 'required|numeric|min:0',
            'materials.*.display_unit' => 'required|string|max:50',
        ];

        if ($request->type === 'area') {
            $rules['area_units'] = 'required|array';
            $rules['area_units.*'] = 'required|numeric|min:0';
            $rules['thickness_units'] = 'required|array';
            $rules['thickness_units.*'] = 'required|numeric|min:0';
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $id) {
            $project = EstimatorProject::findOrFail($id);
            $project->update([
                'type' => $request->type,
                'name' => $request->name,
                'status' => $request->status
            ]);

            EstimatorProjectUnit::where('project_id', $project->id)->delete();
            if ($request->type === 'area') {
                if ($request->has('area_units')) {
                    foreach ($request->area_units as $unit_id => $value) {
                        EstimatorProjectUnit::create(['project_id' => $project->id, 'unit_id' => $unit_id, 'unit_value' => $value]);
                    }
                }
                if ($request->has('thickness_units')) {
                    foreach ($request->thickness_units as $unit_id => $value) {
                        EstimatorProjectUnit::create(['project_id' => $project->id, 'unit_id' => $unit_id, 'unit_value' => $value]);
                    }
                }
            }

            EstimatorMaterial::where('project_id', $project->id)->delete();
            foreach ($request->materials as $mat) {
                EstimatorMaterial::create([
                    'project_id' => $project->id,
                    'category_id' => $mat['category_id'],
                    'variant_id' => $mat['variant_id'], // Saving variant_id
                    'base_quantity' => $mat['base_quantity'],
                    'display_unit' => $mat['display_unit']
                ]);
            }
        });

        return redirect()->route('admin.estimator.index')->with('success', 'Estimator rule updated successfully.');
    }

    public function destroy($id)
    {
        $project = EstimatorProject::findOrFail($id);
        $project->update(['trash' => 1]);
        return redirect()->route('admin.estimator.index')->with('success', 'Estimator rule removed.');
    }

    // CHANGED: Fetches Variants instead of Parent Products
    public function get_variants_by_category($category_id)
    {
        $variants = ProductVariant::with('product:id,name')
            ->whereHas('product', function ($q) use ($category_id) {
                $q->where('category_id', $category_id)
                    ->where('status', 1)
                    ->where('trash', 0);
            })
            ->where('status', 1)
            ->where('trash', 0)
            ->get();

        $formatted = $variants->map(function ($v) {
            return [
                'id' => $v->id,
                'name' => ($v->product->name ?? 'Product') . ' - ' . $v->variant_name
            ];
        });

        return response()->json($formatted);
    }

    public function store_unit_ajax(Request $request)
    {
        $request->validate([
            'type' => 'required|in:area,thickness',
            'unit_name' => 'required|string|max:50'
        ]);

        $unit = EstimatorMeasurementUnit::create([
            'type' => $request->type,
            'unit_name' => $request->unit_name,
            'status' => 1
        ]);

        return response()->json([
            'success' => true,
            'unit' => $unit
        ]);
    }
}