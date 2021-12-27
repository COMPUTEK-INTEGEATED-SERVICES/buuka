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
Route::middleware(['cors', 'json.response', 'guest'])->group(function (){
    Route::post('register', [\App\Http\Controllers\API\AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [\App\Http\Controllers\API\AuthenticationController::class, 'login'])->name('login');
    Route::get('logout', [\App\Http\Controllers\API\AuthenticationController::class, 'logout']);

    Route::post('send_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'sendPasswordResetToken']);
    Route::post('submit_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'submitPasswordResetToken']);

    Route::get('service/{service_id}', [\App\Http\Controllers\API\ServicesController::class, 'getService']);

    Route::get('category/all', [\App\Http\Controllers\API\CategoryController::class, 'getAllCategories']);
});
Route::middleware(['auth:api', 'cors', 'json.response'])->group(function (){
    Route::get('config', [\App\Http\Controllers\API\InitController::class, 'config']);

    //service routes
    Route::post('service/create', [\App\Http\Controllers\API\ServicesController::class, 'addService']);
    Route::post('service/edit', [\App\Http\Controllers\API\ServicesController::class, 'editService']);
    Route::post('service/delete', [\App\Http\Controllers\API\ServicesController::class, 'deleteService']);
    Route::post('service/delete_image', [\App\Http\Controllers\API\ServicesController::class, 'deleteServiceImage']);
    Route::post('service/add_image', [\App\Http\Controllers\API\ServicesController::class, 'addServiceImage']);

    //chat routes
    Route::post('chat/send_message', [\App\Http\Controllers\API\ChatController::class, 'sendMessage']);
    Route::get('chat/get_messages', [\App\Http\Controllers\API\ChatController::class, 'getMessages']);

    //category routes
    //TODO limit this route set to spartie permission
    Route::post('category/add', [\App\Http\Controllers\API\CategoryController::class, 'addCategory']);
    Route::post('category/edit', [\App\Http\Controllers\API\CategoryController::class, 'editCategory']);
    Route::get('category/delete', [\App\Http\Controllers\API\CategoryController::class, 'deleteCategory']);
});
