<?php

namespace App\Http\Controllers\admin\masterdata;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bank; // Make sure to create this model
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Exception;

class BankController extends Controller
{
    public function addBank()
    {
        return view('admin.masterdata.bank.add_bank');
    }

    public function storeBank(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('banks', 'name')->where(function ($query) {
                    return $query->where('trash', 0);
                }),
            ],
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required|in:Active,Inactive'
        ], [
            'name.required' => 'The bank name is required.',
            'name.unique' => 'This bank name already exists.',
            'logo.required' => 'Please upload a bank logo.',
            'logo.image' => 'The uploaded file must be an image.',
            'status.required' => 'Please select a status.'
        ]);

        try {
            $logoName = null;
            if ($request->hasFile('logo')) {
                $image = $request->file('logo');
                $logoName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/banks'), $logoName);
            }

            $bank = Bank::create([
                'name' => trim($request->name),
                'logo' => $logoName,
                'status' => $request->status === 'Active' ? 1 : 0,
                'trash' => 0,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bank added successfully!',
                    'id' => $bank->id,
                    'name' => $bank->name
                ]);
            }

            return redirect()->route('manage_bank')->with('success', 'Bank added successfully!');

        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function manageBank(Request $request)
    {
        $query = Bank::where('trash', 0);

        // Search Filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('name', 'like', "\%{$search}%");
        }

        // Status Filter
        if ($request->filled('status')) {
            $statusVal = $request->status === 'Active' ? 1 : ($request->status === 'Inactive' ? 0 : null);
            if ($statusVal !== null) {
                $query->where('status', $statusVal);
            }
        }

        $banks = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.masterdata.bank.manage_bank', compact('banks'));
    }

    public function updateBank(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('banks', 'name')
                    ->ignore($request->bank_id)
                    ->where(function ($query) {
                        return $query->where('trash', 0);
                    }),
            ],
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'status' => 'required|in:Active,Inactive'
        ], [
            'name.required' => 'The bank name is required.',
            'name.unique' => 'This bank name already exists.',
            'status.required' => 'Please select a status.'
        ]);

        try {
            $bank = Bank::findOrFail($request->bank_id);
            $logoName = $bank->logo;

            // Handle new logo upload
            if ($request->hasFile('logo')) {
                // Delete the old logo if it exists
                if ($bank->logo && File::exists(public_path('uploads/banks/' . $bank->logo))) {
                    File::delete(public_path('uploads/banks/' . $bank->logo));
                }

                // Upload the new logo
                $image = $request->file('logo');
                $logoName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/banks'), $logoName);
            }

            $bank->update([
                'name' => trim($request->name),
                'logo' => $logoName,
                'status' => $request->status === 'Active' ? 1 : 0,
            ]);

            return redirect()->route('manage_bank')->with('success', 'Bank updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteBank($id)
    {
        try {
            $bank = Bank::findOrFail($id);
            $bank->update(['trash' => 1]);

            // Optional: If you want to permanently delete the logo file on soft delete, uncomment below
            // if ($bank->logo && File::exists(public_path('uploads/banks/' .$bank->logo))) {
            //     File::delete(public_path('uploads/banks/' . $bank->logo));
            // }

            return redirect()->route('manage_bank')->with('success', 'Bank deleted successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}