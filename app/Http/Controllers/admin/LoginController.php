<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use Exception;
use Illuminate\Support\Facades\Http;

class LoginController extends Controller
{

    public function login()
    {
        return Auth::guard('admin')->check()
            ? redirect()->route('admin.dashboard')
            : view('admin.login');
    }

    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Merge status and trash checks into the attempt array
        $remember = $request->filled('rememberMe');
        $authAttempt = array_merge($credentials, ['status' => 1, 'trash' => 0]);

        if (Auth::guard('admin')->attempt($authAttempt, $remember)) {
            $admin = Auth::guard('admin')->user();

            // Regenerate session to prevent Session Fixation attacks
            $request->session()->regenerate();
            session(['admin_id' => $admin->id, 'admin_name' => $admin->name]);

            $this->logLoginDetails($request, $admin);

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withInput($request->only('email'))
            ->with('error', 'Invalid credentials or account deactivated.');
    }

    private function logLoginDetails(Request $request, $admin)
    {
        $location = $request->input('location_data');
        $ip = $request->ip();

        // Use Laravel's HTTP client with a strict timeout so login doesn't hang
        if (empty($location) || $location == "Permission Denied") {
            if (!in_array($ip, ['127.0.0.1', '::1'])) {
                try {
                    $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");
                    if ($response->successful() && $response->json('status') === 'success') {
                        $location = $response->json('city') . ', ' . $response->json('regionName');
                    }
                } catch (Exception $e) {
                    $location = "Unknown (API Timeout)";
                }
            } else {
                $location = "Localhost";
            }
        }

        DB::table('login_logs')->insert([
            'admin_id' => $admin->id,
            'ip_address' => $ip,
            'location' => $location,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'login_at' => now(),
        ]);
    }

    public function logoutold()
    {
        Auth::guard('admin')->logout();
        session()->forget(['admin_id', 'admin_name']);
        return redirect()->route('admin.login');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    // Test Controller Method to create the admin
    public function createTestAdmin()
    {
        $exists = DB::table('admins')->where('email', 'admin@a1buildcart.com')->first();
        if (!$exists) {
            DB::table('admins')->insert([
                'name' => 'Admin A1 Build Cart',
                'email' => 'admin@a1buildcart.com',
                'password' => Hash::make('123123'), // Default password
                'mobile' => '9999999999',
                'status' => 1,
                'trash' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            return "Admin Created Successfully. Password: 123123";
        }
        return "Admin already exists.";
    }

    public function updateProfile(Request $request)
    {
        $adminId = Auth::guard('admin')->id();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $adminId,
            'mobile' => 'nullable|numeric'
        ]);

        DB::table('admins')->where('id', $adminId)->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'updated_at' => now()
        ]);

        // Update session data so the header changes immediately without logout
        session(['admin_name' => $request->name]);

        return back()->with('success', 'Profile updated successfully!');
    }

    public function changePassword()
    {
        return view('admin.change_password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:6|different:old_password',
            'confirm_password' => 'required|same:new_password',
        ], [
            'new_password.different' => 'The new password must be different from the old password.',
            'confirm_password.same' => 'The confirmation password does not match.'
        ]);

        $admin = Auth::guard('admin')->user();

        // Verify if old password matches
        if (!Hash::check($request->old_password, $admin->password)) {
            return back()->with('error', 'The current password you entered is incorrect.');
        }

        // Update Password
        DB::table('admins')->where('id', $admin->id)->update([
            'password' => Hash::make($request->new_password),
            'updated_at' => now()
        ]);

        return back()->with('success', 'Password updated successfully!');
    }

    // Step 1: Send OTP
    public function sendOtp(Request $request)
    {
        // Using Eloquent Model find
        $admin = Admin::where('email', $request->email)->where('trash', 0)->first();

        if (!$admin) {
            return response()->json(['status' => 404, 'message' => 'Email not found in our records.']);
        }

        $otp = rand(100000, 999999);

        // Eloquent update
        $admin->update([
            'otp' => $otp,
            'otp_status' => 0
        ]);

        // Masking mobile: Last 5 digits visible
        $maskedMobile = str_repeat('*', strlen($admin->mobile) - 5) . substr($admin->mobile, -5);

        return response()->json([
            'status' => 200,
            'otp' => $otp,
            'mobile' => $maskedMobile,
            'message' => 'OTP sent successfully to your registered mobile.'
        ]);
    }

    // Step 2: Verify OTP
    public function verifyOtp(Request $request)
    {
        $admin = Admin::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('trash', 0)
            ->first();

        if ($admin) {
            $admin->update(['otp_status' => 1]);
            return response()->json(['status' => 200, 'message' => 'OTP Verified Successfully.']);
        }

        return response()->json(['status' => 400, 'message' => 'Invalid OTP. Please try again.']);
    }

    // Step 3: Reset Password
    public function resetPasswordold(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password'
        ]);

        // Ensure the admin exists and OTP was verified in Step 2
        $admin = Admin::where('email', $request->email)
            ->where('otp_status', 1)
            ->where('trash', 0)
            ->first();

        if ($admin) {
            $admin->update([
                'password' => Hash::make($request->new_password),
                'otp' => null,
                'otp_status' => 0
                // 'updated_at' is handled automatically by Eloquent
            ]);
            return response()->json(['status' => 200, 'message' => 'Password reset successful. Please login.']);
        }

        return response()->json(['status' => 400, 'message' => 'Unauthorized action or session expired.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'new_password' => 'required|min:8|max:20',
            'confirm_password' => 'required|same:new_password'
        ]);

        $admin = Admin::where('email', $request->email)
            ->where('otp_status', 1)
            ->where('trash', 0)
            ->first();

        if (!$admin) {
            return response()->json(['status' => 400, 'message' => 'Session expired. Please verify OTP again.']);
        }

        $admin->update([
            'password' => Hash::make($request->new_password),
            'otp' => null,
            'otp_status' => 0
        ]);

        return response()->json(['status' => 200, 'message' => 'Password reset successful.']);
    }
}