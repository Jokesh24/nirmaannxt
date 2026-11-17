<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\VendorFeedback;
use App\Models\VendorEnquiry;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\BusinessType;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Exception;

class VendorController extends Controller
{
    public function add()
    {
        $businessTypes = BusinessType::where('status', 1)->where('trash', 0)->get();
        $productCategories = ProductCategory::where('trash', 0)->where('status', 1)->get();
        return view('admin.vendor.add_vendor', compact('businessTypes', 'productCategories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_name' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'contact_person' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'business_mobile' => 'required|digits:10',
            'business_email' => 'required|email|unique:vendors,business_email',
            'business_type_id' => 'required|integer',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gst_certificate' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'cancelled_cheque' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'delivery_radius_km' => 'required|numeric|min:1',
        ], [
            'business_name.regex' => 'The business name should only contain letters.',
            'contact_person.regex' => 'The contact person name should only contain letters.',
            'business_mobile.digits' => 'The mobile number must be exactly 10 digits.',
            'latitude.required' => 'Please pin the exact location on the map.',
            'longitude.required' => 'Please pin the exact location on the map.'
        ]);

        try {
            $imageName = $this->uploadFile($request, 'company_logo', 'company_logos');
            $gstCertName = $this->uploadFile($request, 'gst_certificate', 'vendor_docs');
            $chequeName = $this->uploadFile($request, 'cancelled_cheque', 'vendor_docs');

            $statusMapping = ['Active' => 1, 'Inactive' => 0, 'Blocked' => 2];

            Vendor::create([
                'business_name' => $request->business_name,
                'contact_person' => $request->contact_person,
                'business_mobile' => $request->business_mobile,
                'business_email' => $request->business_email,
                'business_type_id' => $request->business_type_id,
                'gst_number' => $request->gst_number,
                'pan_number' => $request->pan_number,
                'gst_certificate' => $gstCertName,
                'status' => $statusMapping[$request->status] ?? 1,
                'trash' => 0,
                'company_logo' => $imageName,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'delivery_radius_km' => $request->delivery_radius_km,
                'description' => $request->description,
                'products_supplied' => $request->products_supplied ?? [],
                'daily_capacity' => $request->daily_capacity,
                'weighbridge_available' => $request->weighbridge_available ? 1 : 0,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'cancelled_cheque' => $chequeName,
                'added_by' => Auth::guard('admin')->id() ?? 1,
            ]);

            return redirect()->route('manage_vendor')->with('success', 'Vendor added successfully!');

        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function manage(Request $request)
    {
        $query = Vendor::where('trash', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%$search%")
                    ->orWhere('contact_person', 'like', "%$search%")
                    ->orWhere('business_email', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $statusVal = ['Active' => 1, 'Inactive' => 0, 'Blocked' => 2];
            if (isset($statusVal[$request->status])) {
                $query->where('status', $statusVal[$request->status]);
            }
        }

        $vendors = $query->orderBy('position', 'asc')->orderBy('id', 'desc')->paginate(10);
        return view('admin.vendor.manage_vendor', compact('vendors'));
    }

    public function updatePosition(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'position' => 'required|integer'
        ]);

        try {
            $vendor = Vendor::findOrFail($request->vendor_id);
            $vendor->position = $request->position;
            $vendor->save();

            return response()->json(['success' => true, 'message' => 'Position updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
        }
    }

    public function view($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('admin.vendor.view_vendor', compact('vendor'));
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        $businessTypes = BusinessType::where('status', 1)->where('trash', 0)->get();
        $productCategories = ProductCategory::where('trash', 0)->where('status', 1)->get();

        return view('admin.vendor.edit_vendor', compact('vendor', 'businessTypes', 'productCategories'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'business_name' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'contact_person' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'business_mobile' => 'required|digits:10',
            'business_email' => 'required|email|unique:vendors,business_email,' . $id,
            'business_type_id' => 'required|integer',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'gst_certificate' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'cancelled_cheque' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'delivery_radius_km' => 'required|numeric|min:1',
        ]);

        try {
            $vendor = Vendor::findOrFail($id);

            $imageName = $this->updateFile($request, 'company_logo', 'company_logos', $vendor->company_logo);
            $gstCertName = $this->updateFile($request, 'gst_certificate', 'vendor_docs', $vendor->gst_certificate);
            $chequeName = $this->updateFile($request, 'cancelled_cheque', 'vendor_docs', $vendor->cancelled_cheque);

            $statusMapping = ['Active' => 1, 'Inactive' => 0, 'Blocked' => 2];

            $vendor->update([
                'business_name' => $request->business_name,
                'contact_person' => $request->contact_person,
                'business_mobile' => $request->business_mobile,
                'business_email' => $request->business_email,
                'business_type_id' => $request->business_type_id,
                'gst_number' => $request->gst_number,
                'pan_number' => $request->pan_number,
                'gst_certificate' => $gstCertName,
                'status' => $statusMapping[$request->status] ?? 1,
                'company_logo' => $imageName,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'delivery_radius_km' => $request->delivery_radius_km,
                'description' => $request->description,
                'products_supplied' => $request->products_supplied ?? [],
                'daily_capacity' => $request->daily_capacity,
                'weighbridge_available' => $request->weighbridge_available ? 1 : 0,
                'bank_account_name' => $request->bank_account_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'cancelled_cheque' => $chequeName,
            ]);

            return redirect()->route('manage_vendor')->with('success', 'Vendor updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->update(['trash' => 1]);
            return redirect()->route('manage_vendor')->with('success', 'Vendor moved to trash successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed.');
        }
    }

    // Helper functions to manage file uploads cleanly
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