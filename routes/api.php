<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/
Route::middleware('cors')->group(function (){
    Route::post('register', [\App\Http\Controllers\API\AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [\App\Http\Controllers\API\AuthenticationController::class, 'login'])->name('login');
    Route::get('logout', [\App\Http\Controllers\API\AuthenticationController::class, 'logout']);

    Route::post('send_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'sendPasswordResetToken']);
    Route::post('submit_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'submitPasswordResetToken']);
});
Route::middleware(['auth:api', 'cors'])->group(function (){
    Route::get('config', [\App\Http\Controllers\API\InitController::class, 'config']);
});
