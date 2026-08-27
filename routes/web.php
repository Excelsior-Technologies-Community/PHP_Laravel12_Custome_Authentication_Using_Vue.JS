<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Middleware\TrackCustomerSession;


/*
|--------------------------------------------------------------------------
| Public Pages
|--------------------------------------------------------------------------
*/

Route::get('/', fn() => view('welcome'));

Route::get('/login', fn() => view('layouts.app'));

Route::get('/register', fn() => view('layouts.app'));

Route::get('/forgot-password', fn() => view('layouts.app'));

Route::get('/reset-password', fn() => view('layouts.app'));


/*
|--------------------------------------------------------------------------
| Authentication
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
| Email Verification
|--------------------------------------------------------------------------
*/

Route::get('/verify-email/{token}', [
    CustomerAuthController::class,
    'verifyEmail'
]);

Route::post('/resend-verification', [
    CustomerAuthController::class,
    'resendVerification'
])->middleware('auth');


/*
|--------------------------------------------------------------------------
| Two Factor Authentication
|--------------------------------------------------------------------------
*/

Route::post('/verify-2fa', [
    CustomerAuthController::class,
    'verifyTwoFactor'
]);


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

    Route::get(
        '/dashboard',
        fn() =>
        view('layouts.app')
    );

    Route::get(
        '/profile',
        fn() =>
        view('layouts.app')
    );

    Route::get(
        '/security',
        fn() =>
        view('layouts.app')
    );


    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/api/profile', [
        CustomerAuthController::class,
        'profile'
    ]);

    Route::post('/api/profile', [
        CustomerAuthController::class,
        'updateProfile'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Password
    |--------------------------------------------------------------------------
    */

    Route::post('/api/password/change', [
        CustomerAuthController::class,
        'updatePassword'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Two Factor
    |--------------------------------------------------------------------------
    */

    Route::post('/api/2fa/toggle', [
        CustomerAuthController::class,
        'toggleTwoFactor'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Avatar
    |--------------------------------------------------------------------------
    */

    Route::post('/api/avatar/upload', [
        CustomerAuthController::class,
        'uploadAvatar'
    ]);

    Route::delete('/api/avatar', [
        CustomerAuthController::class,
        'deleteAvatar'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Activity Logs
    |--------------------------------------------------------------------------
    */

    Route::get('/api/activity-logs', [
        CustomerAuthController::class,
        'activityLogs'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    Route::get('/api/security', [
        CustomerAuthController::class,
        'securityDashboard'
    ]);


    /*
    |--------------------------------------------------------------------------
    | Sessions
    |--------------------------------------------------------------------------
    */

    Route::delete(
        '/api/security/sessions/{session}',
        [
            CustomerAuthController::class,
            'revokeSession'
        ]
    );

    Route::delete(
        '/api/security/sessions',
        [
            CustomerAuthController::class,
            'revokeOtherSessions'
        ]
    );

    Route::get('/two-factor', fn () =>
    view('layouts.app')
);


    /*
    |--------------------------------------------------------------------------
    | Account
    |--------------------------------------------------------------------------
    */

    Route::post('/api/account/deactivate', [
        CustomerAuthController::class,
        'deactivateAccount'
    ]);

    Route::delete('/api/account', [
        CustomerAuthController::class,
        'deleteAccount'
    ]);
});
