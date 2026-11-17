<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCoupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

class DiscountCouponController extends Controller
{
    public function add()
    {
        return view('admin.coupons.add_coupon');
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'title' => 'required|max:255',
            'code' => 'required|max:100|unique:discount_coupons,code',
            'coupon_type' => 'required|in:Normal,Bill Threshold',
            'discount_type' => 'required|in:Percentage,Flat',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'threshold_amount' => 'nullable|required_if:coupon_type,Bill Threshold|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date_format:d-m-Y',
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',
            'status' => 'required'
        ]);

        try {
            $imageName = null;
            if ($request->hasFile('logo')) {
                $image = $request->file('logo');
                $imageName = time() . '_coupon_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/coupon_logos'), $imageName);
            }

            $statusMapping = ['Active' => 1, 'Inactive' => 0, 'Expired' => 2];

            DiscountCoupon::create([
                'logo' => $imageName,
                'title' => $request->title,
                'code' => strtoupper($request->code),
                'coupon_type' => $request->coupon_type,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'min_order_amount' => $request->min_order_amount,
                'threshold_amount' => $request->threshold_amount,
                'max_discount' => $request->max_discount,
                'usage_limit' => $request->usage_limit,
                'start_date' => Carbon::createFromFormat('d-m-Y', $request->start_date)->format('Y-m-d'),
                'end_date' => Carbon::createFromFormat('d-m-Y', $request->end_date)->format('Y-m-d'),
                'description' => $request->description,
                'status' => $statusMapping[$request->status] ?? 1,
                'trash' => 0,
                'added_by' => Auth::guard('admin')->id() ?? 1,
            ]);

            return redirect()->route('manage_coupon')->with('success', 'Coupon added successfully!');

        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function manage(Request $request)
    {
        $query = DiscountCoupon::where('trash', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('code', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $statusVal = ['Active' => 1, 'Inactive' => 0, 'Expired' => 2];
            if (isset($statusVal[$request->status])) {
                $query->where('status', $statusVal[$request->status]);
            }
        }

        $coupons = $query->orderBy('position', 'asc')->orderBy('id', 'desc')->paginate(10);

        return view('admin.coupons.manage_coupon', compact('coupons'));
    }

    public function updatePosition(Request $request)
    {
        $request->validate([
            'coupon_id' => 'required|exists:discount_coupons,id',
            'position' => 'required|integer'
        ]);

        try {
            $coupon = DiscountCoupon::findOrFail($request->coupon_id);
            $coupon->position = $request->position;
            $coupon->save();

            return response()->json(['success' => true, 'message' => 'Position updated successfully.']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Update failed.'], 500);
        }
    }

    public function view($id)
    {
        $coupon = DiscountCoupon::findOrFail($id);
        return view('admin.coupons.view_coupon', compact('coupon'));
    }

    public function edit($id)
    {
        $coupon = DiscountCoupon::findOrFail($id);
        return view('admin.coupons.edit_coupon', compact('coupon'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'title' => 'required|max:255',
            'code' => 'required|max:100|unique:discount_coupons,code,' . $id,
            'coupon_type' => 'required|in:Normal,Bill Threshold',
            'discount_type' => 'required|in:Percentage,Flat',
            'discount_value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'threshold_amount' => 'nullable|required_if:coupon_type,Bill Threshold|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'start_date' => 'required|date_format:d-m-Y',
            'end_date' => 'required|date_format:d-m-Y|after_or_equal:start_date',
            'status' => 'required'
        ]);

        try {
            $coupon = DiscountCoupon::findOrFail($id);
            $imageName = $coupon->logo;

            if ($request->hasFile('logo')) {
                if ($imageName && file_exists(public_path('uploads/coupon_logos/' . $imageName))) {
                    unlink(public_path('uploads/coupon_logos/' . $imageName));
                }

                $image = $request->file('logo');
                $imageName = time() . '_coupon_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/coupon_logos'), $imageName);
            }

            $statusMapping = ['Active' => 1, 'Inactive' => 0, 'Expired' => 2];

            $coupon->update([
                'logo' => $imageName,
                'title' => $request->title,
                'code' => strtoupper($request->code),
                'coupon_type' => $request->coupon_type,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'min_order_amount' => $request->min_order_amount,
                'threshold_amount' => $request->threshold_amount,
                'max_discount' => $request->max_discount,
                'usage_limit' => $request->usage_limit,
                'start_date' => Carbon::createFromFormat('d-m-Y', $request->start_date)->format('Y-m-d'),
                'end_date' => Carbon::createFromFormat('d-m-Y', $request->end_date)->format('Y-m-d'),
                'description' => $request->description,
                'status' => $statusMapping[$request->status] ?? 1,
            ]);

            return redirect()->route('manage_coupon')->with('success', 'Coupon updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $coupon = DiscountCoupon::findOrFail($id);
            $coupon->update(['trash' => 1]);
            return redirect()->route('manage_coupon')->with('success', 'Coupon moved to trash successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed.');
        }
    }
}