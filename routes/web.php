<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Middleware\TrackCustomerSession;

/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('welcome'));

Route::get('/login', fn () => view('layouts.app'));

Route::get('/register', fn () => view('layouts.app'));

Route::get('/forgot-password', fn () => view('layouts.app'));

Route::get('/reset-password', fn () => view('layouts.app'));


/*
|--------------------------------------------------------------------------
| Authentication Actions
|--------------------------------------------------------------------------
*/

Route::post('/register', [
    CustomerAuthController::class,
    'register'
]);

Route::post('/login', [
    CustomerAuthController::class,
    'login'
]);

Route::post('/logout', [
    CustomerAuthController::class,
    'logout'
])->middleware('auth');


/*
|--------------------------------------------------------------------------
| Check Email
|--------------------------------------------------------------------------
*/

Route::post('/check-email', [
    CustomerAuthController::class,
    'checkEmail'
]);


/*
|--------------------------------------------------------------------------
| Forgot Password
|--------------------------------------------------------------------------
*/

Route::post('/forgot-password', [
    CustomerAuthController::class,
    'forgotPassword'
]);


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    TrackCustomerSession::class,
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Vue Pages
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', fn () => view('layouts.app'));

    Route::get('/profile', fn () => view('layouts.app'));

    Route::get('/security', fn () => view('layouts.app'));


    /*
    |--------------------------------------------------------------------------
    | Profile API
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/api/profile',
        [CustomerAuthController::class, 'profile']
    );

    Route::post(
        '/api/profile',
        [CustomerAuthController::class, 'updateProfile']
    );

    Route::post(
        '/api/password/change',
        [CustomerAuthController::class, 'updatePassword']
    );


    /*
    |--------------------------------------------------------------------------
    | Avatar API
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/api/avatar/upload',
        [CustomerAuthController::class, 'uploadAvatar']
    );

    Route::delete(
        '/api/avatar',
        [CustomerAuthController::class, 'deleteAvatar']
    );


    /*
    |--------------------------------------------------------------------------
    | Activity Logs API
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/api/activity-logs',
        [CustomerAuthController::class, 'activityLogs']
    );


    /*
    |--------------------------------------------------------------------------
    | Security Dashboard API
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/api/security',
        [CustomerAuthController::class, 'securityDashboard']
    );


    /*
    |--------------------------------------------------------------------------
    | Session Management
    |--------------------------------------------------------------------------
    */

    // Logout one specific device
    Route::delete(
        '/api/security/sessions/{session}',
        [CustomerAuthController::class, 'revokeSession']
    );

    // Logout all other devices
    Route::delete(
        '/api/security/sessions',
        [CustomerAuthController::class, 'revokeOtherSessions']
    );
});