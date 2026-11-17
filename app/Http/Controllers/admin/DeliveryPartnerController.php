<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeliveryPartner;
use Illuminate\Support\Facades\Auth;
use Exception;

class DeliveryPartnerController extends Controller
{
    public function add()
    {
        return view('admin.delivery_partner.add_delivery_partner');
    }

    public function store(Request $request)
    {
        $request->validate([
            // Personal Info
            'driver_name' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'mobile_number' => 'required|digits:10',
            'alternate_mobile' => 'nullable|digits:10',
            'alternate_relation' => 'nullable|string|max:100|required_with:alternate_mobile',
            'current_address' => 'required|string',
            'languages_known' => 'nullable|array',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // KYC
            'dl_number' => 'required|string|max:50',
            'dl_photo' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'aadhaar_number' => 'required|digits:12',
            'aadhaar_photo' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',

            // Vehicle
            'vehicle_reg_number' => 'required|string|max:50',
            'vehicle_type' => 'required|string|max:100',
            'vehicle_capacity' => 'required|string|max:100',
            'rc_book' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_insurance' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_fitness' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_image_front' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'vehicle_image_back' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'vehicle_image_side' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

            // Payout
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
        ]);

        try {
            // Auto-generate Driver ID (e.g., DRV-1001)
            $lastPartner = DeliveryPartner::orderBy('id', 'desc')->first();
            $nextId = $lastPartner ? $lastPartner->id + 1 : 1;
            $driverId = 'DRV-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

            $adminId = Auth::guard('admin')->id() ?? 1;

            DeliveryPartner::create([
                'driver_id' => $driverId,
                'driver_name' => $request->driver_name,
                'mobile_number' => $request->mobile_number,
                'alternate_mobile' => $request->alternate_mobile,
                'alternate_relation' => $request->alternate_relation,
                'current_address' => $request->current_address,
                'languages_known' => $request->languages_known ?? [],
                'dl_number' => $request->dl_number,
                'aadhaar_number' => $request->aadhaar_number,
                'vehicle_reg_number' => $request->vehicle_reg_number,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_capacity' => $request->vehicle_capacity,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'upi_id' => $request->upi_id,
                'status' => $request->status === 'Active' ? 1 : ($request->status === 'Blocked' ? 2 : 0),

                // Files
                'profile_photo' => $this->uploadFile($request, 'profile_photo', 'delivery_partners'),
                'dl_photo' => $this->uploadFile($request, 'dl_photo', 'delivery_partners/docs'),
                'aadhaar_photo' => $this->uploadFile($request, 'aadhaar_photo', 'delivery_partners/docs'),
                'rc_book' => $this->uploadFile($request, 'rc_book', 'delivery_partners/vehicle'),
                'vehicle_insurance' => $this->uploadFile($request, 'vehicle_insurance', 'delivery_partners/vehicle'),
                'vehicle_fitness' => $this->uploadFile($request, 'vehicle_fitness', 'delivery_partners/vehicle'),
                'vehicle_image_front' => $this->uploadFile($request, 'vehicle_image_front', 'delivery_partners/vehicle'),
                'vehicle_image_back' => $this->uploadFile($request, 'vehicle_image_back', 'delivery_partners/vehicle'),
                'vehicle_image_side' => $this->uploadFile($request, 'vehicle_image_side', 'delivery_partners/vehicle'),

                // Audit
                'created_by' => $adminId,
                'created_by_role' => 'Admin',
            ]);

            return redirect()->route('manage_delivery_partner')->with('success', 'Delivery Partner onboarded successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function manage(Request $request)
    {
        $query = DeliveryPartner::where('trash', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('driver_name', 'like', "%$search%")
                    ->orWhere('driver_id', 'like', "%$search%")
                    ->orWhere('mobile_number', 'like', "%$search%")
                    ->orWhere('vehicle_reg_number', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $statusVal = ['Active' => 1, 'Inactive' => 0, 'Blocked' => 2];
            if (isset($statusVal[$request->status])) {
                $query->where('status', $statusVal[$request->status]);
            }
        }

        $partners = $query->orderBy('position', 'asc')->orderBy('id', 'desc')->paginate(10);
        return view('admin.delivery_partner.manage_delivery_partner', compact('partners'));
    }

    public function edit($id)
    {
        $partner = DeliveryPartner::findOrFail($id);
        return view('admin.delivery_partner.edit_delivery_partner', compact('partner'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            // Validations identical to store, excluding unique constraints checks if any
            'driver_name' => 'required|max:255|regex:/^[a-zA-Z\s]+$/',
            'mobile_number' => 'required|digits:10',
            'alternate_mobile' => 'nullable|digits:10',
            'alternate_relation' => 'nullable|string|max:100|required_with:alternate_mobile',
            'current_address' => 'required|string',
            'languages_known' => 'nullable|array',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            'dl_number' => 'required|string|max:50',
            'dl_photo' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'aadhaar_number' => 'required|digits:12',
            'aadhaar_photo' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',

            'vehicle_reg_number' => 'required|string|max:50',
            'vehicle_type' => 'required|string|max:100',
            'vehicle_capacity' => 'required|string|max:100',
            'rc_book' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_insurance' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_fitness' => 'nullable|mimes:jpeg,png,jpg,pdf|max:5120',
            'vehicle_image_front' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'vehicle_image_back' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'vehicle_image_side' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',

            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_ifsc_code' => 'nullable|string|max:20',
            'upi_id' => 'nullable|string|max:100',
        ]);

        try {
            $partner = DeliveryPartner::findOrFail($id);
            $adminId = Auth::guard('admin')->id() ?? 1;

            $partner->update([
                'driver_name' => $request->driver_name,
                'mobile_number' => $request->mobile_number,
                'alternate_mobile' => $request->alternate_mobile,
                'alternate_relation' => $request->alternate_relation,
                'current_address' => $request->current_address,
                'languages_known' => $request->languages_known ?? [],
                'dl_number' => $request->dl_number,
                'aadhaar_number' => $request->aadhaar_number,
                'vehicle_reg_number' => $request->vehicle_reg_number,
                'vehicle_type' => $request->vehicle_type,
                'vehicle_capacity' => $request->vehicle_capacity,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_ifsc_code' => $request->bank_ifsc_code,
                'upi_id' => $request->upi_id,
                'status' => $request->status === 'Active' ? 1 : ($request->status === 'Blocked' ? 2 : 0),

                // Files
                'profile_photo' => $this->updateFile($request, 'profile_photo', 'delivery_partners', $partner->profile_photo),
                'dl_photo' => $this->updateFile($request, 'dl_photo', 'delivery_partners/docs', $partner->dl_photo),
                'aadhaar_photo' => $this->updateFile($request, 'aadhaar_photo', 'delivery_partners/docs', $partner->aadhaar_photo),
                'rc_book' => $this->updateFile($request, 'rc_book', 'delivery_partners/vehicle', $partner->rc_book),
                'vehicle_insurance' => $this->updateFile($request, 'vehicle_insurance', 'delivery_partners/vehicle', $partner->vehicle_insurance),
                'vehicle_fitness' => $this->updateFile($request, 'vehicle_fitness', 'delivery_partners/vehicle', $partner->vehicle_fitness),
                'vehicle_image_front' => $this->updateFile($request, 'vehicle_image_front', 'delivery_partners/vehicle', $partner->vehicle_image_front),
                'vehicle_image_back' => $this->updateFile($request, 'vehicle_image_back', 'delivery_partners/vehicle', $partner->vehicle_image_back),
                'vehicle_image_side' => $this->updateFile($request, 'vehicle_image_side', 'delivery_partners/vehicle', $partner->vehicle_image_side),

                // Audit
                'updated_by' => $adminId,
                'updated_by_role' => 'Admin',
            ]);

            return redirect()->route('manage_delivery_partner')->with('success', 'Delivery Partner updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function view($id)
    {
        $partner = DeliveryPartner::findOrFail($id);
        return view('admin.delivery_partner.view_delivery_partner', compact('partner'));
    }

    public function delete($id)
    {
        try {
            DeliveryPartner::where('id', $id)->update(['trash' => 1]);
            return redirect()->route('manage_delivery_partner')->with('success', 'Moved to trash.');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed.');
        }
    }

    public function updatePosition(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|exists:delivery_partners,id',
            'position' => 'required|integer'
        ]);

        try {
            $partner = DeliveryPartner::findOrFail($request->partner_id);
            $partner->position = $request->position;
            $partner->save();
            return response()->json(['success' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false], 500);
        }
    }

    // Helper functions
    private function uploadFile($request, $fieldName, $folderName)
    {
        if ($request->hasFile($fieldName)) {
            $file = $request->file($fieldName);
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Create directory if it doesn't exist
            $path = public_path('uploads/' . $folderName);
            if (!\File::isDirectory($path)) {
                \File::makeDirectory($path, 0777, true, true);
            }

            $file->move($path, $fileName);
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