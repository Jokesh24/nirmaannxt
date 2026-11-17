<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EstimatorMeasurementUnit;

class EstimatorUnitController extends Controller
{
    public function index()
    {
        $units = EstimatorMeasurementUnit::orderBy('type', 'asc')->orderBy('id', 'asc')->get();
        return view('admin.estimator.units', compact('units'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:area,thickness',
            'unit_name' => 'required|string|max:50',
            'conversion_multiplier' => 'required|numeric|min:0.0001',
        ]);

        EstimatorMeasurementUnit::create([
            'type' => $request->type,
            'unit_name' => $request->unit_name,
            'conversion_multiplier' => $request->conversion_multiplier,
            'is_base_unit' => 0, // Admin-added units are never base units
            'status' => 1
        ]);

        return redirect()->back()->with('success', 'Measurement unit added successfully.');
    }

    public function destroy($id)
    {
        $unit = EstimatorMeasurementUnit::findOrFail($id);

        if ($unit->is_base_unit == 1) {
            return redirect()->back()->with('error', 'Base units (Sq.Ft / Inches) cannot be deleted as they power the core estimation engine.');
        }

        $unit->delete();
        return redirect()->back()->with('success', 'Unit removed successfully.');
    }
}