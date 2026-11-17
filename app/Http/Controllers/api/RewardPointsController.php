<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RewardPointsController extends Controller
{
    /**
     * Main Loyalty Dashboard (Maps to screen 1 in image_ae8161.png)
     */
    public function getDashboard(Request $request)
    {
        $user = auth()->user();

        $pointsRecord = DB::table('user_reward_points')->where('user_id', $user->id)->first();

        $currentPoints = $pointsRecord ? (float) $pointsRecord->total_points : 0;
        $totalEarned = $pointsRecord ? (float) $pointsRecord->total_earned : 0;
        $bonusEarned = $pointsRecord ? (float) $pointsRecord->bonus_earned : 0;
        $sitesReferred = $pointsRecord ? (int) $pointsRecord->sites_referred : 0;
        $tier = $pointsRecord ? $pointsRecord->tier_status : 'Bronze';

        // Calculate Tier Progress (Dummy thresholds based on UI)
        $nextTier = 'Silver';
        $tierMax = 1000;
        if ($tier === 'Bronze') {
            $nextTier = 'Silver';
            $tierMax = 1000;
        } elseif ($tier === 'Silver') {
            $nextTier = 'Gold';
            $tierMax = 5000;
        } elseif ($tier === 'Gold') {
            $nextTier = 'Platinum';
            $tierMax = 10000;
        }

        $pointsNeeded = max(0, $tierMax - $currentPoints);
        $progressPercent = ($currentPoints / $tierMax) * 100;

        // Fetch recent referrals for bottom section
        $recentReferrals = DB::table('reward_points_transactions')
            ->where('user_id', $user->id)
            ->where('description', 'like', '%Referral%')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'wallet' => [
                    'currentBalance' => $currentPoints,
                    'totalEarned' => $totalEarned,
                    'bonusEarned' => $bonusEarned,
                    'sitesReferred' => $sitesReferred,
                ],
                'tier' => [
                    'currentTier' => $tier,
                    'nextTier' => $nextTier,
                    'progressPoints' => $currentPoints,
                    'targetPoints' => $tierMax,
                    'progressPercentage' => min(100, $progressPercent),
                    'message' => "Earn {$pointsNeeded} more points to reach {$nextTier} Tier"
                ],
                'recent_referrals' => $recentReferrals->map(function ($ref) {
                    return [
                        'id' => $ref->id,
                        'title' => $ref->description,
                        'date' => Carbon::parse($ref->created_at)->format('d M, Y'),
                        'points' => (float) $ref->points,
                        'status' => $ref->status // Completed, Pending
                    ];
                })
            ]
        ]);
    }

    /**
     * Earnings History List (Maps to "Earnings" tab in image_ae8161.png)
     */
    public function getTransactions(Request $request)
    {
        $user = auth()->user();
        $filter = $request->input('filter', 'All'); // All, Credited, Pending, Withdrawn

        $query = DB::table('reward_points_transactions')->where('user_id', $user->id);

        if ($filter === 'Credited') {
            $query->where('transaction_type', 'Credit')->where('status', 'Completed');
        } elseif ($filter === 'Pending') {
            $query->where('status', 'Pending');
        } elseif ($filter === 'Withdrawn') {
            $query->where('transaction_type', 'Debit');
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total()
            ]
        ]);
    }

    /**
     * Handle KYC Submission (Maps to "Withdrawal Details" KYC screen)
     */
    public function submitKyc(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'kycNumber' => 'required|string|max:100',
            'accountHolderName' => 'required|string|max:150',
            'ifscCode' => 'required|string|max:20',
            'panCard' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'passbook' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $panPath = $request->file('panCard')->store('kyc/pan', 'public');
        $passbookPath = $request->file('passbook')->store('kyc/passbook', 'public');

        DB::table('user_kyc_details')->updateOrInsert(
            ['user_id' => $user->id],
            [
                'kyc_number' => $request->kycNumber,
                'account_holder_name' => $request->accountHolderName,
                'ifsc_code' => $request->ifscCode,
                'pan_card_path' => $panPath,
                'passbook_path' => $passbookPath,
                'status' => 'Pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'KYC submitted successfully. Awaiting verification.'
        ]);
    }

    /**
     * Process Withdrawal Request (Maps to "Withdraw" screen)
     * Conversion Rate: 100 points = 1 INR
     */
    public function withdrawPoints(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:500', // Assuming amount is requested in INR
            'bankAccountId' => 'required|exists:user_bank_accounts,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // 1. Check KYC Status
        $kyc = DB::table('user_kyc_details')->where('user_id', $user->id)->first();
        if (!$kyc || $kyc->status !== 'Verified') {
            return response()->json([
                'status' => false,
                'kycRequired' => true,
                'message' => 'Your KYC has to be updated and verified to proceed.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $pointsRecord = DB::table('user_reward_points')->where('user_id', $user->id)->lockForUpdate()->first();

            $requestedAmountInr = (float) $request->amount;
            $pointsToDeduct = $requestedAmountInr * 100; // Conversion: 100 points = 1 INR
            $processingFeeInr = 290; // Fixed fee per UI specs
            $payableToBankInr = $requestedAmountInr - $processingFeeInr;

            // Ensure payable amount doesn't drop below 0 due to fees
            if ($payableToBankInr <= 0) {
                return response()->json(['status' => false, 'message' => 'Withdrawal amount must be greater than the processing fee.'], 400);
            }

            if (!$pointsRecord || $pointsRecord->total_points < $pointsToDeduct) {
                return response()->json(['status' => false, 'message' => 'Insufficient points balance for this INR amount.'], 400);
            }

            // Deduct Points
            DB::table('user_reward_points')->where('user_id', $user->id)->update([
                'total_points' => DB::raw("total_points - $pointsToDeduct"),
                'updated_at' => Carbon::now()
            ]);

            // Create Transaction Record
            $refId = 'TID' . time() . rand(100, 999);
            DB::table('reward_points_transactions')->insert([
                'user_id' => $user->id,
                'reference_id' => null, // Would link to a formal withdrawals table if needed
                'transaction_type' => 'Debit',
                'points' => $pointsToDeduct,
                'description' => "Withdrawal to Bank (₹{$requestedAmountInr}) - Ref: $refId",
                'status' => 'Pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Withdrawal initiated successfully.',
                'data' => [
                    'referenceId' => $refId,
                    'pointsDeducted' => $pointsToDeduct,
                    'requestedAmountInr' => $requestedAmountInr,
                    'processingFeeInr' => $processingFeeInr,
                    'payableAmountInr' => $payableToBankInr,
                    'date' => Carbon::now()->format('d M, Y h:i A'),
                    'creditExpectedOn' => Carbon::now()->addDays(2)->format('d M, Y')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Transaction failed.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get Leaderboard (Maps to "Leaderboard" screen in image_ae8461.png)
     */
    public function getLeaderboard()
    {
        $leaderboard = DB::table('user_reward_points')
            ->join('app_users', 'user_reward_points.user_id', '=', 'app_users.id')
            ->select('app_users.id', 'app_users.name', 'app_users.profile_photo', 'user_reward_points.total_earned')
            ->orderByDesc('user_reward_points.total_earned')
            ->limit(50)
            ->get();

        $ranked = $leaderboard->map(function ($user, $index) {
            return [
                'rank' => $index + 1,
                'name' => $user->name,
                'photo' => $user->profile_photo ? asset($user->profile_photo) : null,
                'points' => (float) $user->total_earned
            ];
        });

        return response()->json(['status' => true, 'data' => $ranked]);
    }


}