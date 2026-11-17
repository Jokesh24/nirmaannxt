<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppUser;
use App\Models\Address;
use App\Models\UserBankAccount;
use Exception;

class AppUserController extends Controller
{
    /**
     * Display a paginated list of App Users with search and filter capabilities.
     */
    public function manage(Request $request)
    {
        $query = AppUser::query();

        // Search by Name, Email, Mobile, or Customer Code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('mobile', 'like', "%$search%")
                    ->orWhere('customer_code', 'like', "%$search%");
            });
        }

        // Filter by Status (0: Inactive, 1: Active, 2: Blocked)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('id', 'desc')->paginate(10);

        return view('admin.app_users.manage_users', compact('users'));
    }

    /**
     * View full details of a specific App User, including Addresses and Banks.
     */
    public function view($id)
    {
        $user = AppUser::findOrFail($id);

        // Fetch all related addresses
        $addresses = Address::where('user_id', $id)->orderBy('id', 'desc')->get();

        // Fetch all related bank accounts with master bank details
        $bankAccounts = UserBankAccount::with('bank')->where('user_id', $id)->orderBy('id', 'desc')->get();

        return view('admin.app_users.view_user', compact('user', 'addresses', 'bankAccounts'));
    }

    /**
     * Update the status of an App User.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:0,1,2'
        ]);

        try {
            $user = AppUser::findOrFail($id);
            $user->status = $request->status;
            $user->save();

            $statusText = [0 => 'Inactive', 1 => 'Active', 2 => 'Blocked'][$request->status];

            return back()->with('success', "User status successfully changed to {$statusText}.");
        } catch (Exception $e) {
            return back()->with('error', 'Something went wrong while updating the status.');
        }
    }
}