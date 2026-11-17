<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\UserBankAccount;
use Illuminate\Support\Facades\Validator;
use App\Traits\PushNotificationTrait; // <--- ADDED TRAIT IMPORT

class BankController extends Controller
{
    use PushNotificationTrait; // <--- USE THE TRAIT HERE
    /**
     * 1. Get Master List of Banks
     * Fetches all active banks from the `banks` table.
     */
    public function getBanks(Request $request)
    {
        // Assuming status 1 = active and trash 0 = not deleted
        $banks = Bank::where('status', 1)->where('trash', 0)->orderBy('name', 'asc')->get();

        $formattedBanks = $banks->map(function ($bank) {
            return [
                'id' => $bank->id,
                'name' => $bank->name,
                // Automatically format the logo URL if the accessor isn't set in the model
                'logo' => $bank->logo ? asset('uploads/banks/' . $bank->logo) : null,
                'status' => $bank->status
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Banks retrieved successfully.',
            'data' => [
                'banks' => $formattedBanks
            ]
        ], 200);
    }


    /**
     * 6. Get User Bank Account By ID
     */
    public function getBankById(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'accountId' => 'required|integer|exists:user_bank_accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Fetch the account only if it belongs to the authenticated user
        $account = UserBankAccount::with('bank')
            ->where('id', $request->accountId)
            ->where('user_id', $user->id)
            ->first();

        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Bank account not found or unauthorized.'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Bank account retrieved successfully.',
            'data' => [
                'bankAccount' => [
                    'id' => $account->id,
                    'bankId' => $account->bank_id,
                    'bankName' => $account->bank?->name,
                    'bankLogo' => $account->bank?->logo
                        ? asset('uploads/banks/' . $account->bank->logo)
                        : null,
                    'accountHolderName' => $account->account_holder_name,
                    'accountNumber' => $account->account_number,
                    'ifscCode' => $account->ifsc_code,
                    'upiId' => $account->upi_id,
                    'isSelected' => $account->is_selected
                ]
            ]
        ], 200);
    }


    /**
     * 2. Store User Bank Account
     */
    public function storeUserBankAccountold(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'bankId' => 'required|integer|exists:banks,id',
            'accountHolderName' => 'required|string|max:255',
            'accountNumber' => 'required|string|max:50',
            'ifscCode' => 'required|string|max:20',
            'upiId' => 'nullable|string|max:100' // Optional as requested
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $bankAccount = new UserBankAccount();
        $bankAccount->user_id = $user->id;
        $bankAccount->bank_id = $request->bankId;
        $bankAccount->account_holder_name = $request->accountHolderName;
        $bankAccount->account_number = $request->accountNumber;
        $bankAccount->ifsc_code = $request->ifscCode;
        $bankAccount->upi_id = $request->upiId;
        $bankAccount->save();

        return response()->json([
            'status' => true,
            'message' => 'Bank account saved successfully.',
            'data' => [
                'bankAccount' => [
                    'id' => $bankAccount->id,
                    'bankId' => $bankAccount->bank_id,
                    'accountHolderName' => $bankAccount->account_holder_name,
                    'accountNumber' => $bankAccount->account_number,
                    'ifscCode' => $bankAccount->ifsc_code,
                    'upiId' => $bankAccount->upi_id
                ]
            ]
        ], 200);
    }

    /**
     * 2. Store User Bank Account
     */
    public function storeUserBankAccount(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'bankId' => 'required|integer|exists:banks,id',
            'accountHolderName' => 'required|string|max:255',
            'accountNumber' => 'required|string|max:50',
            'ifscCode' => 'required|string|max:20',
            'upiId' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $bankAccount = new UserBankAccount();
        $bankAccount->user_id = $user->id;
        $bankAccount->bank_id = $request->bankId;
        $bankAccount->account_holder_name = $request->accountHolderName;
        $bankAccount->account_number = $request->accountNumber;
        $bankAccount->ifsc_code = $request->ifscCode;
        $bankAccount->upi_id = $request->upiId;
        $bankAccount->save();

        // ---> FIRE NOTIFICATION HERE <---
        $bankName = Bank::find($request->bankId)->name ?? 'your bank';
        $title = "Bank Account Added";
        $message = "Your account ending in " . substr($request->accountNumber, -4) . " with $bankName has been successfully added.";

        $this->sendAndSaveNotification(
            $user->id,
            $user->device_token, // Make sure your users table has this column populated
            $title,
            $message,
            'bank_added'
        );

        return response()->json([
            'status' => true,
            'message' => 'Bank account saved successfully.',
            'data' => [
                'bankAccount' => [
                    'id' => $bankAccount->id,
                    'bankId' => $bankAccount->bank_id,
                    'accountHolderName' => $bankAccount->account_holder_name,
                    'accountNumber' => $bankAccount->account_number,
                    'ifscCode' => $bankAccount->ifsc_code,
                    'upiId' => $bankAccount->upi_id
                ]
            ]
        ], 200);
    }

    /**
     * 3. Get User Bank Accounts
     */
    public function getUserBankAccounts(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        // Fetch accounts along with the related bank details
        $bankAccounts = UserBankAccount::with('bank')
            ->where('user_id', $user->id)
            ->orderBy('id', 'desc')
            ->get();

        $formattedAccounts = $bankAccounts->map(function ($account) {
            return [
                'id' => $account->id,
                'bankId' => $account->bank_id,
                'bankName' => $account->bank ? $account->bank->name : null,
                'bankLogo' => ($account->bank && $account->bank->logo) ? asset('uploads/banks/' . $account->bank->logo) : null,
                'accountHolderName' => $account->account_holder_name,
                'accountNumber' => $account->account_number,
                'ifscCode' => $account->ifsc_code,
                'upiId' => $account->upi_id,
                'isSelected' => $account->is_selected
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Bank accounts retrieved successfully.',
            'data' => [
                'bankAccounts' => $formattedAccounts
            ]
        ], 200);
    }

    public function setDefaultBank(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'bankId' => 'required|integer|exists:user_bank_accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify ownership before modifying records
        $targetAddress = UserBankAccount::where('id', $request->bankId)->where('user_id', $user->id)->first();

        if (!$targetAddress) {
            return response()->json([
                'status' => false,
                'message' => 'Bank account not found or unauthorized.'
            ], 404);
        }

        // Bulk update: Set all addresses for this user to 0
        UserBankAccount::where('user_id', $user->id)->update(['is_selected' => 0]);

        // Target update: Set the requested address to 1
        $targetAddress->is_selected = 1;
        $targetAddress->save();

        return response()->json([
            'status' => true,
            'message' => 'Bank account selected successfully.',
            'data' => [
                'bankId' => $targetAddress->id,
                'isSelected' => true
            ]
        ], 200);
    }

    /**
     * 4. Edit User Bank Account
     */
    public function updateUserBankAccount(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'accountId' => 'required|integer|exists:user_bank_accounts,id',
            'bankId' => 'sometimes|required|integer|exists:banks,id',
            'accountHolderName' => 'sometimes|required|string|max:255',
            'accountNumber' => 'sometimes|required|string|max:50',
            'ifscCode' => 'sometimes|required|string|max:20',
            'upiId' => 'sometimes|nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Strict ownership verification
        $bankAccount = UserBankAccount::where('id', $request->accountId)->where('user_id', $user->id)->first();

        if (!$bankAccount) {
            return response()->json([
                'status' => false,
                'message' => 'Bank account not found or unauthorized.'
            ], 404);
        }

        if ($request->has('bankId'))
            $bankAccount->bank_id = $request->bankId;
        if ($request->has('accountHolderName'))
            $bankAccount->account_holder_name = $request->accountHolderName;
        if ($request->has('accountNumber'))
            $bankAccount->account_number = $request->accountNumber;
        if ($request->has('ifscCode'))
            $bankAccount->ifsc_code = $request->ifscCode;
        if ($request->has('upiId'))
            $bankAccount->upi_id = $request->upiId;

        $bankAccount->save();

        return response()->json([
            'status' => true,
            'message' => 'Bank account updated successfully.',
            'data' => [
                'bankAccount' => [
                    'id' => $bankAccount->id,
                    'bankId' => $bankAccount->bank_id,
                    'accountHolderName' => $bankAccount->account_holder_name,
                    'accountNumber' => $bankAccount->account_number,
                    'ifscCode' => $bankAccount->ifsc_code,
                    'upiId' => $bankAccount->upi_id
                ]
            ]
        ], 200);
    }

    /**
     * 5. Delete User Bank Account
     */
    public function deleteUserBankAccount(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid account status.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'accountId' => 'required|integer|exists:user_bank_accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Strict ownership verification
        $bankAccount = UserBankAccount::where('id', $request->accountId)->where('user_id', $user->id)->first();

        if (!$bankAccount) {
            return response()->json([
                'status' => false,
                'message' => 'Bank account not found or unauthorized.'
            ], 404);
        }

        $bankAccount->delete();

        return response()->json([
            'status' => true,
            'message' => 'Bank account deleted successfully.'
        ], 200);
    }
}