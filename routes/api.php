<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::group(['prefix' => 'auth'], function ($router) {
    Route::post('register', [\App\Http\Controllers\AuthController::class,'register']);
    Route::post('active-user', [\App\Http\Controllers\AuthController::class,'userActive']);
    Route::post('login', [\App\Http\Controllers\AuthController::class,'login']);
    Route::post('logout', [\App\Http\Controllers\AuthController::class,'logout']);
//    Route::post('refresh', 'AuthController@refresh');
    Route::get('profile', [\App\Http\Controllers\AuthController::class,'profile']);
    Route::post('reset-otp', [\App\Http\Controllers\AuthController::class,'resetOtpEmail']);
});
