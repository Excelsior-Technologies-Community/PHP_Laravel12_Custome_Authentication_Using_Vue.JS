<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;

// Public pages
Route::get('/', fn () => view('welcome'));
Route::get('/login', fn () => view('layouts.app'));
Route::get('/register', fn () => view('layouts.app'));
Route::get('/forgot-password', fn () => view('layouts.app'));
Route::get('/reset-password', fn () => view('layouts.app'));

// Auth actions
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);
Route::post('/logout', [CustomerAuthController::class, 'logout'])->middleware('auth');

// Check email availability
Route::post('/check-email', [CustomerAuthController::class, 'checkEmail']);

// Forgot password
Route::post('/forgot-password', [CustomerAuthController::class, 'forgotPassword']);

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('layouts.app'));
    Route::get('/profile', fn () => view('layouts.app'));

    // Profile
    Route::get('/api/profile', [CustomerAuthController::class, 'profile']);
    Route::post('/api/profile', [CustomerAuthController::class, 'updateProfile']);
    Route::post('/api/password/change', [CustomerAuthController::class, 'updatePassword']);

    // Avatar
    Route::post('/api/avatar/upload', [CustomerAuthController::class, 'uploadAvatar']);
    Route::delete('/api/avatar', [CustomerAuthController::class, 'deleteAvatar']);

    // Activity logs
    Route::get('/api/activity-logs', [CustomerAuthController::class, 'activityLogs']);
});
