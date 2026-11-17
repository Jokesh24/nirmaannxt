<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EstimatorProject;
use App\Models\EstimatorMeasurementUnit;
use App\Models\EstimatorProjectUnit;
use Illuminate\Support\Facades\Validator;

class EstimatorApiController extends Controller
{
    /**
     * 1. Get Estimator Configuration & Units
     */
    public function getEstimatorConfig(Request $request)
    {
        $areaUnits = EstimatorMeasurementUnit::where('type', 'area')
            ->where('status', 1)
            ->select('id', 'unit_name as unitName')
            ->orderBy('id', 'asc')
            ->get();

        $thicknessUnits = EstimatorMeasurementUnit::where('type', 'thickness')
            ->where('status', 1)
            ->select('id', 'unit_name as unitName')
            ->orderBy('id', 'asc')
            ->get();

        $areaRules = EstimatorProject::where('type', 'area')
            ->where('status', 1)
            ->where('trash', 0)
            ->select('id', 'name', 'type')
            ->orderBy('id', 'desc')
            ->get();

        $bhkRules = EstimatorProject::where('type', 'bhk')
            ->where('status', 1)
            ->where('trash', 0)
            ->select('id', 'name', 'type')
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Estimator configuration retrieved successfully.',
            'data' => [
                'units' => [
                    'areaUnits' => $areaUnits,
                    'thicknessUnits' => $thicknessUnits,
                ],
                'rules' => [
                    'byArea' => $areaRules,
                    'byHouseType' => $bhkRules,
                ]
            ]
        ], 200);
    }

    /**
     * 2. Calculate AI Material Estimate
     */
    public function calculateEstimate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:area,bhk',
            'ruleId' => 'required|integer|exists:estimator_projects,id',
            'areaValue' => 'required_if:type,area|nullable|numeric|min:0.01',
            'areaUnitId' => 'required_if:type,area|nullable|integer|exists:estimator_measurement_units,id',
            'thicknessValue' => 'required_if:type,area|nullable|numeric|min:0.01',
            'thicknessUnitId' => 'required_if:type,area|nullable|integer|exists:estimator_measurement_units,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // UPDATED: Eager load the Variant, the Product, and the strict Product Category
        $project = EstimatorProject::with([
            'materials.variant.product.brand',
            'materials.variant.product.category',
            'projectUnits'
        ])
            ->where('id', $request->ruleId)
            ->where('status', 1)
            ->where('trash', 0)
            ->first();

        if (!$project) {
            return response()->json([
                'status' => false,
                'message' => 'Selected estimation rule is inactive or unavailable.'
            ], 404);
        }

        $totalMultiplier = 1;
        $estimationNote = '';

        if ($request->type === 'area') {
            $areaProjectUnit = EstimatorProjectUnit::where('project_id', $project->id)
                ->where('unit_id', $request->areaUnitId)
                ->first();

            $thicknessProjectUnit = EstimatorProjectUnit::where('project_id', $project->id)
                ->where('unit_id', $request->thicknessUnitId)
                ->first();

            $areaFactor = $areaProjectUnit ? (float) $areaProjectUnit->unit_value : 1.0;
            $thicknessFactor = $thicknessProjectUnit ? (float) $thicknessProjectUnit->unit_value : 1.0;

            $totalMultiplier = ($request->areaValue * $areaFactor) * ($request->thicknessValue * $thicknessFactor);

            $areaUnitName = EstimatorMeasurementUnit::where('id', $request->areaUnitId)->value('unit_name') ?? '';
            $thicknessUnitName = EstimatorMeasurementUnit::where('id', $request->thicknessUnitId)->value('unit_name') ?? '';
            $estimationNote = "Based on: {$request->areaValue} {$areaUnitName} • {$request->thicknessValue} {$thicknessUnitName} Thickness";
        } else {
            $totalMultiplier = 1;
            $estimationNote = "Based on: {$project->name}";
        }

        $materialBreakdown = [];
        $subTotalMrp = 0;
        $subTotalAmount = 0;

        foreach ($project->materials as $mat) {

            $variant = $mat->variant;

            if (!$variant || $variant->status == 0 || $variant->trash == 1) {
                continue;
            }

            $product = $variant->product;
            if (!$product) {
                continue;
            }

            // 1. Calculate the TOTAL raw material needed based on the admin's rule
            $rawRequired = $totalMultiplier * (float) $mat->base_quantity;

            // 2. Fetch the packaged quantity of the variant
            $variantQty = ((float) $variant->qty > 0) ? (float) $variant->qty : 1.0;

            // 3. Divide raw required by the variant's package size and round UP
            $itemsToBuy = (float) ceil($rawRequired / $variantQty);

            // 4. Calculate Costs
            $activePrice = ($variant->offer_price > 0) ? (float) $variant->offer_price : (float) $variant->price;
            $mrpPrice = (float) $variant->price;

            $itemCost = $activePrice * $itemsToBuy;
            $itemMrpCost = $mrpPrice * $itemsToBuy;

            $subTotalAmount += $itemCost;
            $subTotalMrp += $itemMrpCost;

            $materialBreakdown[] = [
                'materialId' => $mat->id,
                // STRICT MAPPING: Fetching Category directly from Product instead of the mapping table
                'categoryId' => $product->category_id,
                'categoryName' => $product->category ? $product->category->name : 'Material',
                'productId' => $product->id,
                'productName' => $product->name,
                'brandName' => $product->brand ? $product->brand->brand_name : null,
                'image' => $variant->image ? asset('public/uploads/products/' . $variant->image) : ($product->image ? asset('public/uploads/products/' . $product->image) : null),
                'requiredQty' => $itemsToBuy,
                'unit' => $mat->display_unit,
                'activePrice' => $activePrice,
                'mrpPrice' => $mrpPrice,
                'totalCost' => $itemCost,
                'formattedCost' => '₹' . number_format($itemCost, 0),
                'primaryVariant' => [
                    'id' => $variant->id,
                    'variantName' => $variant->variant_name,
                    'sku' => $variant->sku,
                    'qty' => (float) $variant->qty,
                    'unit' => $variant->unit
                ]
            ];
        }

        $totalSavings = max(0, $subTotalMrp - $subTotalAmount);
        $deliveryCharges = ($subTotalAmount > 0 && $subTotalAmount < 10000) ? 500 : 0;
        $taxes = 0;
        $grandTotal = $subTotalAmount + $deliveryCharges + $taxes;

        return response()->json([
            'status' => true,
            'message' => 'Estimation completed successfully!',
            'data' => [
                'note' => $estimationNote,
                'breakdown' => $materialBreakdown,
                'summary' => [
                    'totalItems' => count($materialBreakdown),
                    'subTotalMrp' => (float) $subTotalMrp,
                    'subTotalAmount' => (float) $subTotalAmount,
                    'totalSavings' => (float) $totalSavings,
                    'deliveryCharges' => (float) $deliveryCharges,
                    'isDeliveryFree' => $deliveryCharges === 0,
                    'taxes' => (float) $taxes,
                    'grandTotal' => (float) $grandTotal,
                    'formattedGrandTotal' => '₹' . number_format($grandTotal, 0),
                    'priceLockedForHours' => 24
                ]
            ]
        ], 200);
    }
}