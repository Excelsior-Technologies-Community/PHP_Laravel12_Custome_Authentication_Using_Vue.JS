<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerAuthController;


Route::get('/', fn () => view('layouts.app'));
Route::get('/login', fn () => view('layouts.app'));
Route::get('/register', fn () => view('layouts.app'));
Route::get('/dashboard', fn () => view('layouts.app'))->middleware('auth');

/* Actions */
Route::post('/register', [CustomerAuthController::class, 'register']);
Route::post('/login', [CustomerAuthController::class, 'login']);
Route::post('/logout', [CustomerAuthController::class, 'logout']);

Route::get('/', function () {
    return view('welcome');
});
