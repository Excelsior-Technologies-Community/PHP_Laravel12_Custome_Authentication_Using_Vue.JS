<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ActivityLog;
use App\Models\CustomerSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\DB;
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
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
            'password_changed_at' => now(),
        ]);

        Auth::login($customer);

        $request->session()->regenerate();

        $sessionToken = Str::random(80);

        $request->session()->put(
            'customer_session_token',
            $sessionToken
        );

        $sessionInfo = $this->getDeviceInformation($request);

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

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
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
                'message' => 'Too many login attempts. Please try again in ' .
                    ceil(RateLimiter::availableIn($key) / 60) .
                    ' minutes.',
            ], 429);
        }

        $credentials = $request->only('email', 'password');

        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {

            $request->session()->regenerate();

            RateLimiter::clear($key);

            $customer = Auth::user();

            $sessionToken = Str::random(80);

            $request->session()->put(
                'customer_session_token',
                $sessionToken
            );

            $sessionInfo = $this->getDeviceInformation($request);

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
                'login',
                'User logged in successfully',
                [
                    'browser' => $sessionInfo['browser'],
                    'platform' => $sessionInfo['platform'],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $customer,
            ]);
        }

        RateLimiter::hit($key, 300);

        /*
        |--------------------------------------------------------------------------
        | FAILED LOGIN ACTIVITY
        |--------------------------------------------------------------------------
        */

        $customer = Customer::where(
            'email',
            $request->email
        )->first();

        if ($customer) {
            $this->createActivityLog(
                $customer,
                $request,
                'failed_login',
                'Failed login attempt',
                [
                    'email' => $request->email,
                ]
            );
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $customer = Auth::user();

        $sessionToken = $request->session()->get(
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
            'message' => 'Logged out successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK EMAIL
    |--------------------------------------------------------------------------
    */

    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'message' => 'Invalid email format',
            ]);
        }

        $exists = Customer::where(
            'email',
            $request->email
        )->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists
                ? 'Email already registered'
                : 'Email is available',
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

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers')
                    ->ignore($customer->id),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $this->createActivityLog(
            $customer,
            $request,
            'profile_update',
            'Profile updated'
        );

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
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

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check(
            $request->current_password,
            $customer->password
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $customer->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        $this->createActivityLog(
            $customer,
            $request,
            'password_change',
            'Password changed'
        );

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AVATAR UPLOAD
    |--------------------------------------------------------------------------
    */

    public function uploadAvatar(Request $request)
    {
        $customer = Auth::user();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadDir = public_path('avatars');

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (
            $customer->avatar &&
            file_exists($uploadDir . '/' . $customer->avatar)
        ) {
            @unlink(
                $uploadDir . '/' . $customer->avatar
            );
        }

        $avatarName =
            time() . '_' .
            uniqid() . '.' .
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
            'message' => 'Avatar uploaded successfully',
            'avatar' => $avatarName,
            'avatar_url' => '/avatars/' . $avatarName,
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

        $uploadDir = public_path('avatars');

        if (
            $customer->avatar &&
            file_exists($uploadDir . '/' . $customer->avatar)
        ) {
            @unlink(
                $uploadDir . '/' . $customer->avatar
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
            'message' => 'Avatar removed successfully',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FORGOT PASSWORD
    |--------------------------------------------------------------------------
    */

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:customers,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where(
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
            'message' => 'Password reset link sent to your email',
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

        $logs = ActivityLog::where(
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

        $currentToken = $request->session()->get(
            'customer_session_token'
        );

        $activeSessions = CustomerSession::where(
            'customer_id',
            $customer->id
        )
            ->latest('last_activity_at')
            ->get()
            ->map(function ($session) use ($currentToken) {

                $session->is_current =
                    $session->session_token === $currentToken;

                $session->is_active =
                    $session->last_activity_at &&
                    $session->last_activity_at
                        ->greaterThan(now()->subMinutes(30));

                return $session;
            });

        $lastLogin = ActivityLog::where(
            'customer_id',
            $customer->id
        )
            ->where('action', 'login')
            ->latest('created_at')
            ->first();

        $failedLogins = ActivityLog::where(
            'customer_id',
            $customer->id
        )
            ->where('action', 'failed_login')
            ->count();

        $recentFailedLogins = ActivityLog::where(
            'customer_id',
            $customer->id
        )
            ->where('action', 'failed_login')
            ->where(
                'created_at',
                '>=',
                now()->subDays(30)
            )
            ->count();

        $recentActivities = ActivityLog::where(
            'customer_id',
            $customer->id
        )
            ->latest('created_at')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,

            'security' => [
                'account_created_at' => $customer->created_at,

                'last_login_at' => $lastLogin?->created_at,

                'last_login_ip' => $lastLogin?->ip_address,

                'password_changed_at' =>
                    $customer->password_changed_at,

                'active_sessions_count' =>
                    $activeSessions
                        ->where('is_active', true)
                        ->count(),

                'total_sessions_count' =>
                    $activeSessions->count(),

                'failed_login_attempts' =>
                    $failedLogins,

                'failed_login_attempts_30_days' =>
                    $recentFailedLogins,
            ],

            'sessions' => $activeSessions,

            'recent_activities' => $recentActivities,
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

    /*
    |--------------------------------------------------------------------------
    | Verify Session Belongs To Current Customer
    |--------------------------------------------------------------------------
    */

    if ($session->customer_id !== $customer->id) {

        return response()->json([
            'success' => false,
            'message' => 'Unauthorized.',
        ], 403);

    }


    /*
    |--------------------------------------------------------------------------
    | Get Current Session Token
    |--------------------------------------------------------------------------
    */

    $currentToken =
        $request->session()->get(
            'customer_session_token'
        );


    /*
    |--------------------------------------------------------------------------
    | Prevent Revoking Current Session
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Store Session Information Before Delete
    |--------------------------------------------------------------------------
    */

    $sessionId =
        $session->id;

    $revokedIp =
        $session->ip_address;

    $revokedBrowser =
        $session->browser;

    $revokedPlatform =
        $session->platform;


    /*
    |--------------------------------------------------------------------------
    | Delete Session
    |--------------------------------------------------------------------------
    */

    $session->delete();


    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    */

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
    | LOGOUT ALL OTHER DEVICES
    |--------------------------------------------------------------------------
    */

    public function revokeOtherSessions(Request $request)
    {
        $customer = Auth::user();

        $currentToken = $request->session()->get(
            'customer_session_token'
        );

        $sessions = CustomerSession::where(
            'customer_id',
            $customer->id
        )
            ->where(
                'session_token',
                '!=',
                $currentToken
            )
            ->get();

        $count = $sessions->count();

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
                'sessions_revoked' => $count,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $count > 0
                ? $count . ' other device session(s) logged out successfully.'
                : 'No other active sessions found.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DEVICE INFORMATION
    |--------------------------------------------------------------------------
    */

    private function getDeviceInformation(Request $request): array
    {
        $userAgent = $request->userAgent() ?? '';

        $browser = 'Unknown Browser';

        if (stripos($userAgent, 'Edg') !== false) {
            $browser = 'Microsoft Edge';
        } elseif (stripos($userAgent, 'Chrome') !== false) {
            $browser = 'Google Chrome';
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $browser = 'Mozilla Firefox';
        } elseif (
            stripos($userAgent, 'Safari') !== false &&
            stripos($userAgent, 'Chrome') === false
        ) {
            $browser = 'Safari';
        } elseif (stripos($userAgent, 'Opera') !== false) {
            $browser = 'Opera';
        }

        $platform = 'Unknown';

        if (stripos($userAgent, 'Windows') !== false) {
            $platform = 'Windows';
        } elseif (stripos($userAgent, 'Macintosh') !== false) {
            $platform = 'macOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $platform = 'Linux';
        } elseif (
            stripos($userAgent, 'Android') !== false
        ) {
            $platform = 'Android';
        } elseif (
            stripos($userAgent, 'iPhone') !== false ||
            stripos($userAgent, 'iPad') !== false
        ) {
            $platform = 'iOS';
        }

        return [
            'device_name' => $platform . ' Device',
            'browser' => $browser,
            'platform' => $platform,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVITY LOG HELPER
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
            'customer_id' => $customer->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => !empty($metadata)
                ? $metadata
                : null,
            'created_at' => now(),
        ]);
    }
}