<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ActivityLog;
use App\Models\CustomerSession;
use App\Models\PasswordHistory;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTER
    |--------------------------------------------------------------------------
    */

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:customers,email',
            ],

            'password' => [
                'required',
                'min:6',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $verificationToken = Str::random(64);

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),

            'email_verified_at' => null,

            'email_verification_token' =>
            $verificationToken,

            'password_changed_at' => now(),

            'is_active' => true,

            'two_factor_enabled' => false,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PASSWORD HISTORY
        |--------------------------------------------------------------------------
        */

        PasswordHistory::create([
            'customer_id' => $customer->id,
            'password' => $customer->password,
        ]);

        /*
        |--------------------------------------------------------------------------
        | LOGIN
        |--------------------------------------------------------------------------
        */

        Auth::login($customer);

        $request->session()->regenerate();

        $sessionToken = Str::random(80);

        $request->session()->put(
            'customer_session_token',
            $sessionToken
        );

        $sessionInfo =
            $this->getDeviceInformation($request);

        CustomerSession::create([
            'customer_id' => $customer->id,
            'session_token' => $sessionToken,
            'device_name' => $sessionInfo['device_name'],
            'browser' => $sessionInfo['browser'],
            'platform' => $sessionInfo['platform'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->createActivityLog(
            $customer,
            $request,
            'register',
            'User registered successfully'
        );

        /*
        |--------------------------------------------------------------------------
        | SEND VERIFICATION EMAIL
        |--------------------------------------------------------------------------
        */

        $this->sendVerificationEmail($customer);

        return response()->json([
            'success' => true,

            'message' =>
            'Registration successful. Please verify your email.',

            'user' => $customer,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $key = 'login:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,

                'message' =>
                'Too many login attempts. Please try again in ' .
                    ceil(
                        RateLimiter::availableIn($key) / 60
                    ) .
                    ' minutes.',
            ], 429);
        }

        $customer = Customer::where(
            'email',
            $request->email
        )->first();

        /*
        |--------------------------------------------------------------------------
        | CUSTOMER NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$customer) {

            RateLimiter::hit($key, 300);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | ACCOUNT DEACTIVATED
        |--------------------------------------------------------------------------
        */

        if (!$customer->is_active) {

            return response()->json([
                'success' => false,
                'message' =>
                'Your account is deactivated.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK PASSWORD
        |--------------------------------------------------------------------------
        */

        if (!Hash::check(
            $request->password,
            $customer->password
        )) {

            RateLimiter::hit($key, 300);

            $this->createActivityLog(
                $customer,
                $request,
                'failed_login',
                'Failed login attempt'
            );

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL VERIFICATION
        |--------------------------------------------------------------------------
        */

        if (!$customer->email_verified_at) {

            return response()->json([
                'success' => false,
                'email_not_verified' => true,
                'message' =>
                'Please verify your email before logging in.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | TWO FACTOR AUTHENTICATION
        |--------------------------------------------------------------------------
        */

        if ($customer->two_factor_enabled) {

            $otp = random_int(100000, 999999);

            $customer->update([
                'two_factor_otp' =>
                Hash::make($otp),

                'two_factor_otp_expires_at' =>
                now()->addMinutes(5),
            ]);

            $request->session()->put(
                'two_factor_customer_id',
                $customer->id
            );

            Mail::raw(
                "Your login OTP is: {$otp}. This OTP will expire in 5 minutes.",
                function ($message) use ($customer) {

                    $message
                        ->to($customer->email)
                        ->subject('Your Login OTP');
                }
            );

            return response()->json([
                'success' => true,
                'two_factor_required' => true,
                'message' =>
                'OTP sent to your email.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL LOGIN
        |--------------------------------------------------------------------------
        */

        Auth::login(
            $customer,
            $request->boolean('remember')
        );

        $request->session()->regenerate();

        RateLimiter::clear($key);

        $this->createLoginSession(
            $customer,
            $request
        );

        /*
        |--------------------------------------------------------------------------
        | LOGIN ACTIVITY
        |--------------------------------------------------------------------------
        */

        $sessionInfo =
            $this->getDeviceInformation($request);

        $this->createActivityLog(
            $customer,
            $request,
            'login',
            'User logged in successfully',
            [
                'browser' =>
                $sessionInfo['browser'],

                'platform' =>
                $sessionInfo['platform'],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | LOGIN NOTIFICATION
        |--------------------------------------------------------------------------
        */

        $this->sendLoginNotification(
            $customer,
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $customer,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY TWO FACTOR OTP
    |--------------------------------------------------------------------------
    */

    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'otp' => 'required|digits:6',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $customerId =
            $request->session()->get(
                'two_factor_customer_id'
            );

        if (!$customerId) {

            return response()->json([
                'success' => false,
                'message' =>
                'OTP session expired.',
            ], 422);
        }

        $customer =
            Customer::find($customerId);

        if (!$customer) {

            return response()->json([
                'success' => false,
                'message' =>
                'Customer not found.',
            ], 404);
        }

        if (
            !$customer->two_factor_otp ||
            !$customer->two_factor_otp_expires_at
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                'OTP is invalid.',
            ], 422);
        }

        if (
            now()->greaterThan(
                $customer->two_factor_otp_expires_at
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                'OTP has expired.',
            ], 422);
        }

        if (
            !Hash::check(
                $request->otp,
                $customer->two_factor_otp
            )
        ) {

            $this->createActivityLog(
                $customer,
                $request,
                'failed_2fa',
                'Invalid two factor OTP'
            );

            return response()->json([
                'success' => false,
                'message' =>
                'Invalid OTP.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | CLEAR OTP
        |--------------------------------------------------------------------------
        */

        $customer->update([
            'two_factor_otp' => null,
            'two_factor_otp_expires_at' => null,
        ]);

        $request->session()->forget(
            'two_factor_customer_id'
        );

        /*
        |--------------------------------------------------------------------------
        | LOGIN CUSTOMER
        |--------------------------------------------------------------------------
        */

        Auth::login($customer);

        $request->session()->regenerate();

        $this->createLoginSession(
            $customer,
            $request
        );

        $this->createActivityLog(
            $customer,
            $request,
            'login',
            'User logged in successfully with 2FA'
        );

        $this->sendLoginNotification(
            $customer,
            $request
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Two-factor authentication successful.',
            'user' => $customer,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ENABLE / DISABLE 2FA
    |--------------------------------------------------------------------------
    */

    public function toggleTwoFactor(Request $request)
    {
        $customer = Auth::user();

        $customer->update([
            'two_factor_enabled' =>
            !$customer->two_factor_enabled,
        ]);

        $status =
            $customer->two_factor_enabled
            ? 'enabled'
            : 'disabled';

        $this->createActivityLog(
            $customer,
            $request,
            'two_factor_' . $status,
            'Two factor authentication ' . $status
        );

        return response()->json([
            'success' => true,

            'two_factor_enabled' =>
            $customer->two_factor_enabled,

            'message' =>
            'Two factor authentication ' .
                $status .
                ' successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | RESEND EMAIL VERIFICATION
    |--------------------------------------------------------------------------
    */

    public function resendVerification(Request $request)
    {
        $customer = Auth::user();

        if ($customer->email_verified_at) {

            return response()->json([
                'success' => false,
                'message' =>
                'Email is already verified.',
            ], 422);
        }

        $customer->update([
            'email_verification_token' =>
            Str::random(64),
        ]);

        $this->sendVerificationEmail(
            $customer
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Verification email sent successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY EMAIL
    |--------------------------------------------------------------------------
    */

    public function verifyEmail(
        string $token
    ) {
        $customer =
            Customer::where(
                'email_verification_token',
                $token
            )->first();

        if (!$customer) {

            return redirect(
                '/login?verified=0'
            );
        }

        $customer->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ]);

        $this->createActivityLog(
            $customer,
            request(),
            'email_verified',
            'Email verified successfully'
        );

        return redirect(
            '/login?verified=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ACCOUNT DEACTIVATE
    |--------------------------------------------------------------------------
    */

    public function deactivateAccount(Request $request)
    {
        $customer = Auth::user();

        $customer->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        $this->createActivityLog(
            $customer,
            $request,
            'account_deactivated',
            'Customer account deactivated'
        );

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' =>
            'Your account has been deactivated.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function deleteAccount(Request $request)
{
    $customer = Auth::user();

    /*
    |--------------------------------------------------------------------------
    | Log Account Deletion
    |--------------------------------------------------------------------------
    */

    $this->createActivityLog(
        $customer,
        $request,
        'account_deleted',
        'Customer account deleted'
    );


    /*
    |--------------------------------------------------------------------------
    | Delete Sessions
    |--------------------------------------------------------------------------
    */

    CustomerSession::where(
        'customer_id',
        $customer->id
    )->delete();


    /*
    |--------------------------------------------------------------------------
    | Delete Password History
    |--------------------------------------------------------------------------
    */

    PasswordHistory::where(
        'customer_id',
        $customer->id
    )->delete();


    /*
    |--------------------------------------------------------------------------
    | Delete Activity Logs
    |--------------------------------------------------------------------------
    */

    ActivityLog::where(
        'customer_id',
        $customer->id
    )->delete();


    /*
    |--------------------------------------------------------------------------
    | Delete Customer
    |--------------------------------------------------------------------------
    */

    $customer->delete();


    /*
    |--------------------------------------------------------------------------
    | Logout
    |--------------------------------------------------------------------------
    */

    Auth::logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();


    return response()->json([
        'success' => true,
        'message' =>
            'Your account has been permanently deleted.',
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    public function profile()
    {
        return response()->json([
            'success' => true,
            'user' => Auth::user(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PROFILE
    |--------------------------------------------------------------------------
    */

    public function updateProfile(Request $request)
    {
        $customer = Auth::user();

        $validator = Validator::make(
            $request->all(),
            [
                'name' =>
                'required|string|max:255',

                'email' => [
                    'required',
                    'email',
                    'max:255',

                    Rule::unique('customers')
                        ->ignore($customer->id),
                ],
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL CHANGED
        |--------------------------------------------------------------------------
        */

        if ($customer->email !== $request->email) {

            $customer->update([
                'email' => $request->email,

                'email_verified_at' => null,

                'email_verification_token' =>
                Str::random(64),
            ]);

            $this->sendVerificationEmail(
                $customer->fresh()
            );
        }

        $customer->update([
            'name' => $request->name,
        ]);

        $this->createActivityLog(
            $customer,
            $request,
            'profile_update',
            'Profile updated'
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Profile updated successfully',
            'user' => $customer->fresh(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD
    |--------------------------------------------------------------------------
    */

    public function updatePassword(Request $request)
    {
        $customer = Auth::user();

        $validator = Validator::make(
            $request->all(),
            [
                'current_password' => 'required',

                'password' =>
                'required|min:6|confirmed',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if (
            !Hash::check(
                $request->current_password,
                $customer->password
            )
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                'Current password is incorrect',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | PASSWORD HISTORY
        |--------------------------------------------------------------------------
        */

        $recentPasswords =
            PasswordHistory::where(
                'customer_id',
                $customer->id
            )
            ->latest()
            ->limit(5)
            ->get();

        foreach ($recentPasswords as $history) {

            if (
                Hash::check(
                    $request->password,
                    $history->password
                )
            ) {

                return response()->json([
                    'success' => false,
                    'message' =>
                    'You cannot reuse any of your last 5 passwords.',
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE PASSWORD
        |--------------------------------------------------------------------------
        */

        $newPassword =
            Hash::make($request->password);

        $customer->update([
            'password' => $newPassword,
            'password_changed_at' => now(),
        ]);

        PasswordHistory::create([
            'customer_id' => $customer->id,
            'password' => $newPassword,
        ]);

        /*
        |--------------------------------------------------------------------------
        | KEEP ONLY LAST 5
        |--------------------------------------------------------------------------
        */

        $oldHistory =
            PasswordHistory::where(
                'customer_id',
                $customer->id
            )
            ->latest()
            ->skip(5)
            ->get();

        foreach ($oldHistory as $history) {
            $history->delete();
        }

        $this->createActivityLog(
            $customer,
            $request,
            'password_change',
            'Password changed'
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Password changed successfully',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | TOGGLE PASSWORD VISIBILITY
    |--------------------------------------------------------------------------
    |
    | This is mainly handled in Vue.
    |
    */


    /*
    |--------------------------------------------------------------------------
    | AVATAR UPLOAD
    |--------------------------------------------------------------------------
    */

    public function uploadAvatar(Request $request)
    {
        $customer = Auth::user();

        $validator = Validator::make(
            $request->all(),
            [
                'avatar' =>
                'required|image|max:2048',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadDir =
            public_path('avatars');

        if (!file_exists($uploadDir)) {

            mkdir(
                $uploadDir,
                0755,
                true
            );
        }

        if (
            $customer->avatar &&
            file_exists(
                $uploadDir . '/' .
                    $customer->avatar
            )
        ) {

            @unlink(
                $uploadDir . '/' .
                    $customer->avatar
            );
        }

        $avatarName =
            time() .
            '_' .
            uniqid() .
            '.' .
            $request->avatar->extension();

        $request->avatar->move(
            $uploadDir,
            $avatarName
        );

        $customer->update([
            'avatar' => $avatarName,
        ]);

        $this->createActivityLog(
            $customer,
            $request,
            'avatar_upload',
            'Profile picture uploaded'
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Avatar uploaded successfully',

            'avatar' =>
            $avatarName,

            'avatar_url' =>
            '/avatars/' .
                $avatarName,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE AVATAR
    |--------------------------------------------------------------------------
    */

    public function deleteAvatar(Request $request)
    {
        $customer = Auth::user();

        $uploadDir =
            public_path('avatars');

        if (
            $customer->avatar &&
            file_exists(
                $uploadDir . '/' .
                    $customer->avatar
            )
        ) {

            @unlink(
                $uploadDir . '/' .
                    $customer->avatar
            );
        }

        $customer->update([
            'avatar' => null,
        ]);

        $this->createActivityLog(
            $customer,
            $request,
            'avatar_delete',
            'Profile picture removed'
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Avatar removed successfully',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    */

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'email' =>
                'required|email|exists:customers,email',
            ]
        );

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer =
            Customer::where(
                'email',
                $request->email
            )->first();

        $this->createActivityLog(
            $customer,
            $request,
            'forgot_password',
            'Password reset requested'
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Password reset link sent to your email',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOGS
    |--------------------------------------------------------------------------
    */

    public function activityLogs(Request $request)
    {
        $customer = Auth::user();

        $logs =
            ActivityLog::where(
                'customer_id',
                $customer->id
            )
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SECURITY DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function securityDashboard(Request $request)
    {
        $customer = Auth::user();

        $currentToken =
            $request->session()->get(
                'customer_session_token'
            );

        $activeSessions =
            CustomerSession::where(
                'customer_id',
                $customer->id
            )
            ->latest('last_activity_at')
            ->get()
            ->map(
                function (
                    $session
                ) use (
                    $currentToken
                ) {

                    $session->is_current =
                        $session->session_token ===
                        $currentToken;

                    $session->is_active =
                        $session->last_activity_at &&
                        $session->last_activity_at
                        ->greaterThan(
                            now()->subMinutes(30)
                        );

                    return $session;
                }
            );

        $lastLogin =
            ActivityLog::where(
                'customer_id',
                $customer->id
            )
            ->where(
                'action',
                'login'
            )
            ->latest('created_at')
            ->first();

        $failedLogins =
            ActivityLog::where(
                'customer_id',
                $customer->id
            )
            ->where(
                'action',
                'failed_login'
            )
            ->count();

        $recentFailedLogins =
            ActivityLog::where(
                'customer_id',
                $customer->id
            )
            ->where(
                'action',
                'failed_login'
            )
            ->where(
                'created_at',
                '>=',
                now()->subDays(30)
            )
            ->count();

        $recentActivities =
            ActivityLog::where(
                'customer_id',
                $customer->id
            )
            ->latest('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,

            'security' => [

                'account_created_at' =>
                $customer->created_at,

                'email_verified' =>
                !is_null(
                    $customer->email_verified_at
                ),

                'last_login_at' =>
                $lastLogin?->created_at,

                'last_login_ip' =>
                $lastLogin?->ip_address,

                'password_changed_at' =>
                $customer->password_changed_at,

                'two_factor_enabled' =>
                $customer->two_factor_enabled,

                'account_active' =>
                $customer->is_active,

                'active_sessions_count' =>
                $activeSessions
                    ->where(
                        'is_active',
                        true
                    )
                    ->count(),

                'total_sessions_count' =>
                $activeSessions->count(),

                'failed_login_attempts' =>
                $failedLogins,

                'failed_login_attempts_30_days' =>
                $recentFailedLogins,
            ],

            'sessions' =>
            $activeSessions,

            'recent_activities' =>
            $recentActivities,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | REVOKE ONE SESSION
    |--------------------------------------------------------------------------
    */

    public function revokeSession(
        Request $request,
        CustomerSession $session
    ) {
        $customer = Auth::user();

        if (
            $session->customer_id !==
            $customer->id
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                'Unauthorized.',
            ], 403);
        }

        $currentToken =
            $request->session()->get(
                'customer_session_token'
            );

        if (
            $session->session_token ===
            $currentToken
        ) {

            return response()->json([
                'success' => false,
                'message' =>
                'You cannot revoke your current session. Use Logout instead.',
            ], 422);
        }

        $sessionId =
            $session->id;

        $revokedIp =
            $session->ip_address;

        $revokedBrowser =
            $session->browser;

        $revokedPlatform =
            $session->platform;

        $session->delete();

        $this->createActivityLog(
            $customer,
            $request,
            'session_revoked',
            'A device session was revoked',
            [
                'revoked_session_id' =>
                $sessionId,

                'revoked_ip' =>
                $revokedIp,

                'revoked_browser' =>
                $revokedBrowser,

                'revoked_platform' =>
                $revokedPlatform,
            ]
        );

        return response()->json([
            'success' => true,
            'message' =>
            'Device session revoked successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | LOGOUT OTHER DEVICES
    |--------------------------------------------------------------------------
    */

    public function revokeOtherSessions(
        Request $request
    ) {
        $customer = Auth::user();

        $currentToken =
            $request->session()->get(
                'customer_session_token'
            );

        $sessions =
            CustomerSession::where(
                'customer_id',
                $customer->id
            )
            ->where(
                'session_token',
                '!=',
                $currentToken
            )
            ->get();

        $count =
            $sessions->count();

        CustomerSession::where(
            'customer_id',
            $customer->id
        )
            ->where(
                'session_token',
                '!=',
                $currentToken
            )
            ->delete();

        $this->createActivityLog(
            $customer,
            $request,
            'sessions_revoked',
            'All other device sessions were revoked',
            [
                'sessions_revoked' =>
                $count,
            ]
        );

        return response()->json([
            'success' => true,

            'message' =>
            $count > 0
                ? $count .
                ' other device session(s) logged out successfully.'
                : 'No other active sessions found.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    public function checkEmail(
        Request $request
    ) {
        $validator =
            Validator::make(
                $request->all(),
                [
                    'email' =>
                    'required|email',
                ]
            );

        if ($validator->fails()) {

            return response()->json([
                'available' => false,
                'message' =>
                'Invalid email format',
            ]);
        }

        $exists =
            Customer::where(
                'email',
                $request->email
            )->exists();

        return response()->json([
            'available' =>
            !$exists,

            'message' =>
            $exists
                ? 'Email already registered'
                : 'Email is available',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $customer = Auth::user();

        $sessionToken =
            $request->session()->get(
                'customer_session_token'
            );

        if ($customer) {

            $this->createActivityLog(
                $customer,
                $request,
                'logout',
                'User logged out'
            );

            if ($sessionToken) {

                CustomerSession::where(
                    'session_token',
                    $sessionToken
                )->delete();
            }
        }

        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' =>
            'Logged out successfully',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE LOGIN SESSION
    |--------------------------------------------------------------------------
    */

    private function createLoginSession(
        Customer $customer,
        Request $request
    ): void {

        $sessionToken =
            Str::random(80);

        $request->session()->put(
            'customer_session_token',
            $sessionToken
        );

        $sessionInfo =
            $this->getDeviceInformation(
                $request
            );

        CustomerSession::create([
            'customer_id' =>
            $customer->id,

            'session_token' =>
            $sessionToken,

            'device_name' =>
            $sessionInfo['device_name'],

            'browser' =>
            $sessionInfo['browser'],

            'platform' =>
            $sessionInfo['platform'],

            'ip_address' =>
            $request->ip(),

            'user_agent' =>
            $request->userAgent(),

            'login_at' =>
            now(),

            'last_activity_at' =>
            now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SEND VERIFICATION EMAIL
    |--------------------------------------------------------------------------
    */

    private function sendVerificationEmail(
        Customer $customer
    ): void {

        $url =
            url(
                '/verify-email/' .
                    $customer->email_verification_token
            );

        Mail::raw(
            "Hello {$customer->name},

Please verify your email address by clicking the link below:

{$url}

Thank you.",
            function ($message) use (
                $customer
            ) {

                $message
                    ->to($customer->email)
                    ->subject(
                        'Verify Your Email Address'
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOGIN NOTIFICATION
    |--------------------------------------------------------------------------
    */

    private function sendLoginNotification(
        Customer $customer,
        Request $request
    ): void {

        $sessionInfo =
            $this->getDeviceInformation(
                $request
            );

        Mail::raw(
            "Hello {$customer->name},

A login was detected on your account.

Date:
" . now()->format('Y-m-d H:i:s') .

                "

IP Address:
" . $request->ip() .

                "

Browser:
" . $sessionInfo['browser'] .

                "

Platform:
" . $sessionInfo['platform'] .

                "

If this was not you, please change your password immediately.",
            function ($message) use (
                $customer
            ) {

                $message
                    ->to($customer->email)
                    ->subject(
                        'New Login Detected'
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | DEVICE INFORMATION
    |--------------------------------------------------------------------------
    */

    private function getDeviceInformation(
        Request $request
    ): array {

        $userAgent =
            $request->userAgent() ?? '';

        $browser =
            'Unknown Browser';

        if (
            stripos(
                $userAgent,
                'Edg'
            ) !== false
        ) {

            $browser =
                'Microsoft Edge';
        } elseif (
            stripos(
                $userAgent,
                'Chrome'
            ) !== false
        ) {

            $browser =
                'Google Chrome';
        } elseif (
            stripos(
                $userAgent,
                'Firefox'
            ) !== false
        ) {

            $browser =
                'Mozilla Firefox';
        } elseif (
            stripos(
                $userAgent,
                'Safari'
            ) !== false &&
            stripos(
                $userAgent,
                'Chrome'
            ) === false
        ) {

            $browser =
                'Safari';
        } elseif (
            stripos(
                $userAgent,
                'Opera'
            ) !== false
        ) {

            $browser =
                'Opera';
        }

        $platform =
            'Unknown';

        if (
            stripos(
                $userAgent,
                'Windows'
            ) !== false
        ) {

            $platform =
                'Windows';
        } elseif (
            stripos(
                $userAgent,
                'Macintosh'
            ) !== false
        ) {

            $platform =
                'macOS';
        } elseif (
            stripos(
                $userAgent,
                'Linux'
            ) !== false
        ) {

            $platform =
                'Linux';
        } elseif (
            stripos(
                $userAgent,
                'Android'
            ) !== false
        ) {

            $platform =
                'Android';
        } elseif (
            stripos(
                $userAgent,
                'iPhone'
            ) !== false ||
            stripos(
                $userAgent,
                'iPad'
            ) !== false
        ) {

            $platform =
                'iOS';
        }

        return [

            'device_name' =>
            $platform . ' Device',

            'browser' =>
            $browser,

            'platform' =>
            $platform,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG
    |--------------------------------------------------------------------------
    */

    private function createActivityLog(
        Customer $customer,
        Request $request,
        string $action,
        string $description,
        array $metadata = []
    ): void {

        ActivityLog::create([

            'customer_id' =>
            $customer->id,

            'action' =>
            $action,

            'description' =>
            $description,

            'ip_address' =>
            $request->ip(),

            'user_agent' =>
            $request->userAgent(),

            'metadata' =>
            !empty($metadata)
                ? $metadata
                : null,

            'created_at' =>
            now(),
        ]);
    }
}
