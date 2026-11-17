<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppUser;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;


class AuthController extends Controller
{
    /**
     * Step 1: Login / Send OTP
     * Only returns mobile and the generated OTP.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'deviceId' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric'
        ], [
            'mobile.regex' => 'Please enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = AppUser::where('mobile', $request->mobile)->first();

        if ($user) {
            if ($user->status == 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is currently inactive. Please contact support.'
                ], 403);
            }

            if ($user->status == 2) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account has been blocked. Please contact support.'
                ], 403);
            }
        } else {
            $user = new AppUser();
            $user->mobile = $request->mobile;
            $user->status = 1;

            do {
                $code = 'NNCUST' . rand(1000, 9999);
            } while (AppUser::where('customer_code', $code)->exists());

            $user->customer_code = $code;
        }

        $otp = rand(1000, 9999);

        $user->otp = $otp;
        $user->otp_status = 0;
        $user->device_token = $request->deviceId;

        if ($request->filled('latitude')) {
            $user->latitude = $request->latitude;
        }
        if ($request->filled('longitude')) {
            $user->longitude = $request->longitude;
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'mobile' => $user->mobile,
                'otp' => $user->otp
            ]
        ], 200);
    }

    /**
     * Step 2: Verify OTP and Generate JWT Tokens
     * Returns session-critical data needed for the app's local state.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mobile' => ['required', 'regex:/^[6-9]\d{9}$/'],
            'otp' => 'required|numeric'
        ], [
            'mobile.regex' => 'Please enter a valid 10-digit mobile number.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = AppUser::where('mobile', $request->mobile)
            ->where('otp', $request->otp)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid Mobile Number or OTP'
            ], 401);
        }

        if ($user->status == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is currently inactive. Please contact support.'
            ], 403);
        }

        if ($user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been blocked. Please contact support.'
            ], 403);
        }

        $isNew = (is_null($user->name) || trim($user->name) === '') ? 1 : 0;

        $user->otp = null;
        $user->otp_status = 1;
        $user->save();

        // Generate Access Token (Valid for 15 minutes) with custom identifier
        $accessToken = auth('api')->claims(['token_type' => 'access'])->setTTL(60)->login($user);

        // Generate Refresh Token (Valid for 30 days = 43200 minutes) with custom identifier
        $refreshToken = auth('api')->claims(['token_type' => 'refresh'])->setTTL(43200)->login($user);

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'data' => [
                'isNew' => $isNew,
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'mobile' => $user->mobile,
                    'email' => $user->email,
                    'profilePhoto' => $user->profile_photo,
                    'customerCode' => $user->customer_code
                ]
            ]
        ], 200);
    }

    /**
     * Step 3: Refresh Token API
     * Accepts a valid refreshToken, invalidates it, and returns a fresh pair of tokens.
     */
    /**
     * Step 3: Refresh Token API
     * Accepts a valid refreshToken, invalidates it, and returns a fresh pair of tokens.
     */
    public function refreshToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refreshToken' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Explicitly use the 'api' guard to set the token, ensuring it maps to AppUser
            auth('api')->setToken($request->refreshToken);

            // Extract the payload to verify it is actually a refresh token
            $payload = auth('api')->payload();

            if ($payload->get('token_type') !== 'refresh') {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token type. Please provide a valid refresh token.'
                ], 401);
            }

            // Authenticate the user from the refresh token using the correct API guard
            $user = auth('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found.'
                ], 404);
            }

            // Ensure the user hasn't been blocked since their last login
            if ($user->status == 0 || $user->status == 2) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account is inactive or blocked. Please contact support.'
                ], 403);
            }

            // Invalidate the old refresh token to prevent replay attacks (Token Rotation)
            auth('api')->invalidate();

            // Generate a fresh Access Token (15 mins) and Refresh Token (30 days)
            $accessToken = auth('api')->claims(['token_type' => 'access'])->setTTL(15)->login($user);
            $refreshToken = auth('api')->claims(['token_type' => 'refresh'])->setTTL(43200)->login($user);

            $isNew = (is_null($user->name) || trim($user->name) === '') ? 1 : 0;

            return response()->json([
                'status' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'isNew' => $isNew,
                    'accessToken' => $accessToken,
                    'refreshToken' => $refreshToken,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'mobile' => $user->mobile,
                        'email' => $user->email,
                        'profilePhoto' => $user->profile_photo,
                        'customerCode' => $user->customer_code
                    ]
                ]
            ], 200);

        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Refresh token has expired. Please login again.'
            ], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid refresh token.'
            ], 401);
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Token error. Please try again.'
            ], 500);
        }
    }

    /**
     * Step 3: Update User Name
     * Only returns the updated ID and Name.
     */
    public function updateUserName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150|regex:/^[a-zA-Z\s]+$/'
        ], [
            'name.regex' => 'The name can only contain letters and spaces.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid token.'
            ], 401);
        }

        if ($user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Action forbidden. Your account is inactive or blocked.'
            ], 403);
        }

        $user->name = $request->name;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'User name updated successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name
                ]
            ]
        ], 200);
    }

    /**
     * Step 4: Update Profile Data
     * Only returns the specific fields that are editable in the profile view.
     */
    public function updateProfileData(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid token.'
            ], 401);
        }

        if ($user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Action forbidden. Your account is inactive or blocked.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:150|regex:/^[a-zA-Z\s]+$/',
            'email' => 'sometimes|nullable|email|unique:app_users,email,' . $user->id,
            'gender' => 'sometimes|nullable|in:1,2',
            'profilePhoto' => 'sometimes|nullable|image|mimes:jpeg,png,jpg|max:5120'
        ], [
            'name.regex' => 'The name can only contain letters and spaces.',
            'gender.in' => 'Gender must be 1 (Male) or 2 (Female).',
            'profilePhoto.image' => 'The profile photo must be a valid image file.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if ($request->has('gender')) {
            $user->gender = $request->gender;
        }

        if ($request->hasFile('profilePhoto')) {
            $file = $request->file('profilePhoto');
            $destinationPath = public_path('uploads/user_profile_photos');

            if (!File::exists($destinationPath)) {
                File::makeDirectory($destinationPath, 0755, true, true);
            }

            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $oldPhoto = $user->getRawOriginal('profile_photo');

            if ($oldPhoto && File::exists(public_path($oldPhoto))) {
                File::delete(public_path($oldPhoto));
            }

            $file->move($destinationPath, $fileName);
            $user->profile_photo = 'uploads/user_profile_photos/' . $fileName;
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'gender' => $user->gender,
                    'profilePhoto' => $user->profile_photo
                ]
            ]
        ], 200);
    }

    /**
     * Step 5: Get Full Profile Data
     * Retrieves the complete profile of the authenticated user.
     */
    public function getProfile(Request $request)
    {
        // Retrieve the authenticated user via the JWT Token
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized or invalid token.'
            ], 401);
        }

        // Security check: Ensure the account is still active
        if ($user->status == 0 || $user->status == 2) {
            return response()->json([
                'status' => false,
                'message' => 'Action forbidden. Your account is inactive or blocked.'
            ], 403);
        }

        // Determine the gender label
        $genderLabel = null;
        if ($user->gender == 1) {
            $genderLabel = 'Male';
        } elseif ($user->gender == 2) {
            $genderLabel = 'Female';
        }

        // Return the full profile data mapped to strict camelCase
        return response()->json([
            'status' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    // 'otpStatus' => (int) $user->otp_status,
                    // 'latitude' => $user->latitude,
                    // 'longitude' => $user->longitude,
                    // 'userType' => $user->user_type,
                    'profilePhoto' => $user->profile_photo,
                    'gender' => (int) $user->gender,
                    'genderLabel' => $genderLabel,
                    // 'status' => (int) $user->status,
                    'customerCode' => $user->customer_code,
                    // 'createdAt' => $user->created_at,
                    // 'updatedAt' => $user->updated_at,
                ]
            ]
        ], 200);
    }

    /**
     * Get Total Rewards Earned by the authenticated user
     */
    public function home(Request $request)
    {
        $user = auth()->user();

        // 1. Get Total Rewards Earned
        $pointsRecord = DB::table('user_reward_points')
            ->where('user_id', $user->id)
            ->select('total_earned')
            ->first();

        $totalEarned = $pointsRecord ? (float) $pointsRecord->total_earned : 0;

        // 2. Get Cart Items Count
        // (Uses the count of distinct variants in the cart. Use sum('qty') instead of count() if you want total units)
        $cartCount = \App\Models\Cart::where('user_id', $user->id)->count();

        // 3. Get Wishlist Items Count
        $wishlistCount = \App\Models\Wishlist::where('user_id', $user->id)->count();

        return response()->json([
            'status' => true,
            'message' => 'Home data retrieved successfully.',
            'data' => [
                'totalEarned' => $totalEarned,
                'cartCount' => $cartCount,
                'wishlistCount' => $wishlistCount
            ]
        ], 200);
    }
}