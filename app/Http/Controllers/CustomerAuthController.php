<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\RateLimiter;

class CustomerAuthController extends Controller
{
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
        ]);

        Auth::login($customer);

        ActivityLog::create([
            'customer_id' => $customer->id,
            'action' => 'register',
            'description' => 'User registered successfully',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $customer,
        ]);
    }

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
                'message' => 'Too many login attempts. Please try again in ' . ceil(RateLimiter::availableIn($key) / 60) . ' minutes.',
            ], 429);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            RateLimiter::clear($key);

            $customer = Auth::user();

            ActivityLog::create([
                'customer_id' => $customer->id,
                'action' => 'login',
                'description' => 'User logged in',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => $customer,
            ]);
        }

        RateLimiter::hit($key, 300);

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials',
        ], 401);
    }

    public function logout(Request $request)
    {
        $customer = Auth::user();

        if ($customer) {
            ActivityLog::create([
                'customer_id' => $customer->id,
                'action' => 'logout',
                'description' => 'User logged out',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

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

        $exists = Customer::where('email', $request->email)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Email already registered' : 'Email is available',
        ]);
    }

    public function profile()
    {
        return response()->json([
            'success' => true,
            'user' => Auth::user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $customer = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers')->ignore($customer->id),
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

        ActivityLog::create([
            'customer_id' => $customer->id,
            'action' => 'profile_update',
            'description' => 'Profile updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $customer,
        ]);
    }

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

        if (!Hash::check($request->current_password, $customer->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $customer->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::create([
            'customer_id' => $customer->id,
            'action' => 'password_change',
            'description' => 'Password changed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

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

        if ($customer->avatar && file_exists($uploadDir . '/' . $customer->avatar)) {
            @unlink($uploadDir . '/' . $customer->avatar);
        }

        $avatarName = time() . '_' . uniqid() . '.' . $request->avatar->extension();
        $request->avatar->move($uploadDir, $avatarName);

        $customer->update(['avatar' => $avatarName]);

        ActivityLog::create([
            'customer_id' => $customer->id,
            'action' => 'avatar_upload',
            'description' => 'Profile picture uploaded',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar uploaded successfully',
            'avatar' => $avatarName,
            'avatar_url' => '/avatars/' . $avatarName,
        ]);
    }

    public function deleteAvatar(Request $request)
    {
        $customer = Auth::user();

        $uploadDir = public_path('avatars');
        if ($customer->avatar && file_exists($uploadDir . '/' . $customer->avatar)) {
            @unlink($uploadDir . '/' . $customer->avatar);
        }

        $customer->update(['avatar' => null]);

        ActivityLog::create([
            'customer_id' => $customer->id,
            'action' => 'avatar_delete',
            'description' => 'Profile picture removed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar removed successfully',
        ]);
    }

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

        $customer = Customer::where('email', $request->email)->first();

        ActivityLog::create([
            'customer_id' => $customer->id,
            'action' => 'forgot_password',
            'description' => 'Password reset requested',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email',
        ]);
    }

    public function activityLogs(Request $request)
    {
        $customer = Auth::user();

        $logs = ActivityLog::where('customer_id', $customer->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }
}
