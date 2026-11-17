<?php

namespace App\Http\Controllers\admin\masterdata;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BusinessType;
use Illuminate\Validation\Rule;
use Exception;

class BusinessTypeController extends Controller
{
    public function addBusinessType()
    {
        return view('admin.masterdata.business_type.add_business_type');
    }

    public function storeBusinessType(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('business_types', 'name')->where(function ($query) {
                    return $query->where('trash', 0);
                }),
            ],
            'status' => 'required|in:Active,Inactive'
        ], [
            'name.required' => 'The business type name is required.',
            'name.unique' => 'This business type name already exists.',
            'status.required' => 'Please select a status.'
        ]);

        try {
            $businessType = BusinessType::create([
                'name' => trim($request->name),
                'status' => $request->status === 'Active' ? 1 : 0,
                'trash' => 0,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Business Type added successfully!',
                    'id' => $businessType->id,
                    'name' => $businessType->name
                ]);
            }

            return redirect()->route('manage_business_type')->with('success', 'Business Type added successfully!');

        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function manageBusinessType(Request $request)
    {
        $query = BusinessType::where('trash', 0);

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('name', 'like', "%{$search}%");
        }

        // Status Filter
        if ($request->filled('status')) {
            $statusVal = $request->status === 'Active' ? 1 : ($request->status === 'Inactive' ? 0 : null);
            if ($statusVal !== null) {
                $query->where('status', $statusVal);
            }
        }

        $businessTypes = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.masterdata.business_type.manage_business_type', compact('businessTypes'));
    }

    public function updateBusinessType(Request $request)
    {
        $request->validate([
            'business_type_id' => 'required|exists:business_types,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('business_types', 'name')
                    ->ignore($request->business_type_id)
                    ->where(function ($query) {
                        return $query->where('trash', 0);
                    }),
            ],
            'status' => 'required|in:Active,Inactive'
        ], [
            'name.required' => 'The business type name is required.',
            'name.unique' => 'This business type name already exists.',
            'status.required' => 'Please select a status.'
        ]);

        try {
            $businessType = BusinessType::findOrFail($request->business_type_id);

            $businessType->update([
                'name' => trim($request->name),
                'status' => $request->status === 'Active' ? 1 : 0,
            ]);

            return redirect()->route('manage_business_type')->with('success', 'Business Type updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteBusinessType($id)
    {
        try {
            $businessType = BusinessType::findOrFail($id);
            $businessType->update(['trash' => 1]);

            return redirect()->route('manage_business_type')->with('success', 'Business Type deleted successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}