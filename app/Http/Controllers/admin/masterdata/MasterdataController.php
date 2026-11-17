<?php

namespace App\Http\Controllers\admin\masterdata;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Size;
use App\Models\Unit;
use App\Models\Colour;
use Exception;

class MasterdataController extends Controller
{
    public function addsizes()
    {
        return view('admin.masterdata.size.add_size');
    }

    public function storeSizes(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sizes,name',
            'status' => 'required'
        ]);

        try {
            $size = Size::create([
                'name' => $request->name,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'id' => $size->id,
                    'name' => $size->name
                ]);
            }

            return redirect()->route('manage_size')->with('success', 'Size added successfully!');
        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function manageSizes(Request $request)
    {
        $query = Size::where('trash', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status == 'Active' ? 1 : 0);
        }

        $sizes = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.masterdata.size.manage_size', compact('sizes'));
    }

    public function updateSizes(Request $request)
    {
        $request->validate([
            'size_id' => 'required|exists:sizes,id',
            'name' => 'required|string|max:255|unique:sizes,name,' . $request->size_id,
            'status' => 'required'
        ]);

        try {
            $size = Size::findOrFail($request->size_id);
            $size->update([
                'name' => $request->name,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            return redirect()->route('manage_size')->with('success', 'Size updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteSizes($id)
    {
        Size::where('id', $id)->update(['trash' => 1]);
        return redirect()->route('manage_size')->with('success', 'Size deleted successfully!');
    }

    // ==========================================
    // COLOUR METHODS
    // ==========================================

    public function addColours()
    {
        return view('admin.masterdata.colour.add_colour');
    }

    public function storeColours(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:colours,name',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required'
        ]);

        try {
            $iconPath = null;
            if ($request->hasFile('icon')) {
                $image = $request->file('icon');
                $iconPath = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/colours'), $iconPath);
            }

            $colour = Colour::create([
                'name' => $request->name,
                'icon' => $iconPath,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'id' => $colour->id,
                    'name' => $colour->name
                ]);
            }

            return redirect()->route('manage_colour')->with('success', 'Colour added successfully!');
        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function manageColours(Request $request)
    {
        $query = Colour::where('trash', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status == 'Active' ? 1 : 0);
        }

        $colours = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.masterdata.colour.manage_colour', compact('colours'));
    }

    public function updateColours(Request $request)
    {
        $request->validate([
            'colour_id' => 'required|exists:colours,id',
            'name' => 'required|string|max:255|unique:colours,name,' . $request->colour_id,
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required'
        ]);

        try {
            $colour = Colour::findOrFail($request->colour_id);
            $iconPath = $colour->icon;

            if ($request->hasFile('icon')) {
                if ($iconPath && file_exists(public_path('uploads/colours/' . $iconPath))) {
                    unlink(public_path('uploads/colours/' . $iconPath));
                }
                $image = $request->file('icon');
                $iconPath = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('uploads/colours'), $iconPath);
            }

            $colour->update([
                'name' => $request->name,
                'icon' => $iconPath,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            return redirect()->route('manage_colour')->with('success', 'Colour updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteColours($id)
    {
        Colour::where('id', $id)->update(['trash' => 1]);
        return redirect()->route('manage_colour')->with('success', 'Colour deleted successfully!');
    }



    /* *****************************************
                    Units METHODS
    ******************************************** */

    public function addUnits()
    {
        return view('admin.masterdata.unit.add_unit');
    }

    public function storeUnits(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:units,name',
            'status' => 'required'
        ]);

        try {
            $unit = Unit::create([
                'name' => $request->name,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'id' => $unit->id,
                    'name' => $unit->name
                ]);
            }

            return redirect()->route('manage_unit')->with('success', 'Unit added successfully!');
        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function manageUnits(Request $request)
    {
        $query = Unit::where('trash', 0);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status == 'Active' ? 1 : 0);
        }

        $units = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.masterdata.unit.manage_unit', compact('units'));
    }

    public function updateUnits(Request $request)
    {
        $request->validate([
            'size_id' => 'required|exists:sizes,id',
            'name' => 'required|string|max:255|unique:units,name,' . $request->unit_id,
            'status' => 'required'
        ]);

        try {
            $size = Unit::findOrFail($request->size_id);
            $size->update([
                'name' => $request->name,
                'status' => $request->status == 'Active' ? 1 : 0,
            ]);

            return redirect()->route('manage_units')->with('success', 'Unit updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteUnits($id)
    {
        Unit::where('id', $id)->update(['trash' => 1]);
        return redirect()->route('manage_unit')->with('success', 'Unit deleted successfully!');
    }

}