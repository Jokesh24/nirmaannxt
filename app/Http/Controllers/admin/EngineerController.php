<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Engineer;
use Illuminate\Support\Facades\Auth;
use Exception;

class EngineerController extends Controller
{
    public function add()
    {
        return view('admin.engineer.add_engineer');
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'mobile_number' => 'required|digits:10',
            'email' => 'required|email|unique:engineers,email',
            'highest_qualification' => 'required|string|max:255',
            'assigned_territory' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',  // Added Validation
            'longitude' => 'nullable|numeric', // Added Validation
            'aadhaar_number' => 'required|digits:12',
            'pan_number' => 'nullable|string|max:10',

            // File validations
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'qualification_proof' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'aadhaar_upload' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'pan_upload' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'cancelled_cheque' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        try {
            $lastEngineer = Engineer::orderBy('id', 'desc')->first();
            $nextId = $lastEngineer ? $lastEngineer->id + 1 : 1;
            $engineerId = 'ENG-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $profilePhoto = $this->uploadFile($request, 'profile_photo', 'engineer_profiles');
            $qualProof = $this->uploadFile($request, 'qualification_proof', 'engineer_docs');
            $aadhaarDoc = $this->uploadFile($request, 'aadhaar_upload', 'engineer_docs');
            $panDoc = $this->uploadFile($request, 'pan_upload', 'engineer_docs');
            $chequeDoc = $this->uploadFile($request, 'cancelled_cheque', 'engineer_docs');

            $statusMapping = ['Active' => 1, 'Inactive' => 0, 'Blocked' => 2];

            Engineer::create([
                'engineer_id' => $engineerId,
                'full_name' => $request->full_name,
                'mobile_number' => $request->mobile_number,
                'email' => $request->email,
                'highest_qualification' => $request->highest_qualification,
                'qualification_proof' => $qualProof,
                'profile_photo' => $profilePhoto,
                'assigned_territory' => $request->assigned_territory,
                'latitude' => $request->latitude,   // Added Save
                'longitude' => $request->longitude, // Added Save
                'aadhaar_number' => $request->aadhaar_number,
                'aadhaar_upload' => $aadhaarDoc,
                'pan_number' => $request->pan_number,
                'pan_upload' => $panDoc,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'cancelled_cheque' => $chequeDoc,
                'description' => $request->description,
                'status' => $statusMapping[$request->status] ?? 1,
                'trash' => 0,
                'added_by' => Auth::guard('admin')->id() ?? 1,
            ]);

            return redirect()->route('manage_engineer')->with('success', 'Engineer created successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    // ... manage, updatePosition, view, edit methods remain unchanged ...
    public function manage(Request $request)
    {
        $query = Engineer::where('trash', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%$search%")
                    ->orWhere('engineer_id', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('mobile_number', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $statusVal = ['Active' => 1, 'Inactive' => 0, 'Blocked' => 2];
            if (isset($statusVal[$request->status])) {
                $query->where('status', $statusVal[$request->status]);
            }
        }

        $engineers = $query->orderBy('position', 'asc')->orderBy('id', 'desc')->paginate(10);
        return view('admin.engineer.manage_engineer', compact('engineers'));
    }

    public function updatePosition(Request $request)
    {
        $request->validate([
            'engineer_id' => 'required|exists:engineers,id',
            'position' => 'required|integer'
        ]);

        try {
            $engineer = Engineer::findOrFail($request->engineer_id);
            $engineer->position = $request->position;
            $engineer->save();

            return response()->json(['success' => true, 'message' => 'Position updated successfully.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
        }
    }

    public function view($id)
    {
        $engineer = Engineer::findOrFail($id);
        return view('admin.engineer.view_engineer', compact('engineer'));
    }

    public function edit($id)
    {
        $engineer = Engineer::findOrFail($id);
        return view('admin.engineer.edit_engineer', compact('engineer'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'full_name' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'mobile_number' => 'required|digits:10',
            'email' => 'required|email|unique:engineers,email,' . $id,
            'highest_qualification' => 'required|string|max:255',
            'assigned_territory' => 'required|string|max:255',
            'latitude' => 'nullable|numeric',  // Added Validation
            'longitude' => 'nullable|numeric', // Added Validation
            'aadhaar_number' => 'required|digits:12',
            'pan_number' => 'nullable|string|max:10',

            // File validations
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'qualification_proof' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'aadhaar_upload' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'pan_upload' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'cancelled_cheque' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        try {
            $engineer = Engineer::findOrFail($id);

            $qualProof = $this->updateFile($request, 'qualification_proof', 'engineer_docs', $engineer->qualification_proof);
            $aadhaarDoc = $this->updateFile($request, 'aadhaar_upload', 'engineer_docs', $engineer->aadhaar_upload);
            $panDoc = $this->updateFile($request, 'pan_upload', 'engineer_docs', $engineer->pan_upload);
            $chequeDoc = $this->updateFile($request, 'cancelled_cheque', 'engineer_docs', $engineer->cancelled_cheque);
            $profilePhoto = $this->updateFile($request, 'profile_photo', 'engineer_profiles', $engineer->profile_photo);

            $statusMapping = ['Active' => 1, 'Inactive' => 0, 'Blocked' => 2];

            $engineer->update([
                'full_name' => $request->full_name,
                'mobile_number' => $request->mobile_number,
                'email' => $request->email,
                'highest_qualification' => $request->highest_qualification,
                'qualification_proof' => $qualProof,
                'assigned_territory' => $request->assigned_territory,
                'latitude' => $request->latitude,   // Added Save
                'longitude' => $request->longitude, // Added Save
                'aadhaar_number' => $request->aadhaar_number,
                'aadhaar_upload' => $aadhaarDoc,
                'pan_number' => $request->pan_number,
                'pan_upload' => $panDoc,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'cancelled_cheque' => $chequeDoc,
                'description' => $request->description,
                'status' => $statusMapping[$request->status] ?? 1,
                'profile_photo' => $profilePhoto,
            ]);

            return redirect()->route('manage_engineer')->with('success', 'Engineer updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            Engineer::where('id', $id)->update(['trash' => 1]);
            return redirect()->route('manage_engineer')->with('success', 'Engineer moved to trash.');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed.');
        }
    }

    // Helper functions for file management
    private function uploadFile($request, $fieldName, $folderName)
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/' . $folderName), $fileName);
            return $fileName;
        }
        return null;
    }

    private function updateFile($request, $fieldName, $folderName, $oldFileName)
    {
        if ($request->hasFile($fieldName)) {
            if ($oldFileName && file_exists(public_path('uploads/' . $folderName . '/' . $oldFileName))) {
                unlink(public_path('uploads/' . $folderName . '/' . $oldFileName));
            }
            return $this->uploadFile($request, $fieldName, $folderName);
        }
        return $oldFileName;
    }
}