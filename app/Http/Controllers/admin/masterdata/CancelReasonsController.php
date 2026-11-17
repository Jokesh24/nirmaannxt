<?php

namespace App\Http\Controllers\admin\masterdata;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CancelReason;
use Illuminate\Validation\Rule;
use Exception;

class CancelReasonsController extends Controller
{
    public function addCancelReason()
    {
        return view('admin.masterdata.cancel_reason.add_cancel_reason');
    }

    public function storeCancelReason(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cancel_reasons', 'name')->where(function ($query) {
                    return $query->where('trash', 0);
                }),
            ],
            'status' => 'required|in:Active,Inactive'
        ], [
            'name.required' => 'The cancel reason name is required.',
            'name.unique' => 'This cancel reason name already exists.',
            'status.required' => 'Please select a status.'
        ]);

        try {
            $cancelReason = CancelReason::create([
                'name' => trim($request->name),
                'status' => $request->status === 'Active' ? 1 : 0,
                'trash' => 0,
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cancel Reason added successfully!',
                    'id' => $cancelReason->id,
                    'name' => $cancelReason->name
                ]);
            }

            return redirect()->route('manage_cancel_reason')->with('success', 'Cancel Reason added successfully!');

        } catch (Exception $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }

            return back()->with('error', 'Something went wrong: ' . $e->getMessage())->withInput();
        }
    }

    public function manageCancelReason(Request $request)
    {
        $query = CancelReason::where('trash', 0);

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

        $cancelReasons = $query->orderBy('id', 'desc')->paginate(10);
        return view('admin.masterdata.cancel_reason.manage_cancel_reason', compact('cancelReasons'));
    }

    public function updateCancelReason(Request $request)
    {
        $request->validate([
            'cancel_reason_id' => 'required|exists:cancel_reasons,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cancel_reasons', 'name')
                    ->ignore($request->cancel_reason_id)
                    ->where(function ($query) {
                        return $query->where('trash', 0);
                    }),
            ],
            'status' => 'required|in:Active,Inactive'
        ], [
            'name.required' => 'The cancel reason name is required.',
            'name.unique' => 'This cancel reason name already exists.',
            'status.required' => 'Please select a status.'
        ]);

        try {
            $cancelReason = CancelReason::findOrFail($request->cancel_reason_id);

            $cancelReason->update([
                'name' => trim($request->name),
                'status' => $request->status === 'Active' ? 1 : 0,
            ]);

            return redirect()->route('manage_cancel_reason')->with('success', 'Cancel Reason updated successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    public function deleteCancelReason($id)
    {
        try {
            $cancelReason = CancelReason::findOrFail($id);
            $cancelReason->update(['trash' => 1]);

            return redirect()->route('manage_cancel_reason')->with('success', 'Cancel Reason deleted successfully!');
        } catch (Exception $e) {
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }
}