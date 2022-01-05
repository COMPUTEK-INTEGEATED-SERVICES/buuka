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
Route::middleware(['cors', 'guest'])->group(function (){
    Route::post('register', [\App\Http\Controllers\API\AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [\App\Http\Controllers\API\AuthenticationController::class, 'login'])->name('login');
    Route::get('logout', [\App\Http\Controllers\API\AuthenticationController::class, 'logout']);

    Route::post('send_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'sendPasswordResetToken']);
    Route::post('submit_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'submitPasswordResetToken']);

    Route::get('service', [\App\Http\Controllers\API\SupportController::class, 'getService']);
    Route::get('services', [\App\Http\Controllers\API\SupportController::class, 'getServices']);

    //support routes
    Route::get('countries', [\App\Http\Controllers\API\SupportController::class, 'getCountries']);
    Route::get('states', [\App\Http\Controllers\API\SupportController::class, 'getStates']);
    Route::get('cities', [\App\Http\Controllers\API\SupportController::class, 'getCities']);
    Route::get('category/all', [\App\Http\Controllers\API\SupportController::class, 'getAllCategories']);
    Route::get('weeks', [\App\Http\Controllers\API\SupportController::class, 'getWeeks']);
    Route::get('banks', [\App\Http\Controllers\API\SupportController::class, 'getBanks']);

    //payment routes
    Route::get('payment_settings', [\App\Http\Controllers\API\PaymentController::class, 'payment_settings']);
    Route::get('payment_methods', [\App\Http\Controllers\API\PaymentController::class, 'payment_methods']);
    Route::post('payment/confirm', [\App\Http\Controllers\API\PaymentController::class, 'verifyPayment']);
});
Route::middleware(['auth:api', 'cors'])->group(function (){

    //config route
    Route::get('config', [\App\Http\Controllers\API\InitController::class, 'config']);

    //service routes
    Route::post('service/create', [\App\Http\Controllers\API\ServicesController::class, 'addService']);
    Route::post('service/edit', [\App\Http\Controllers\API\ServicesController::class, 'editService']);
    Route::post('service/delete', [\App\Http\Controllers\API\ServicesController::class, 'deleteService']);
    Route::post('service/delete_image', [\App\Http\Controllers\API\ServicesController::class, 'deleteServiceImage']);
    Route::post('service/add_image', [\App\Http\Controllers\API\ServicesController::class, 'addServiceImage']);

    //chat routes
    Route::post('chat/send_message', [\App\Http\Controllers\API\ChatController::class, 'sendMessage']);
    Route::get('chat/get_messages_with_user', [\App\Http\Controllers\API\ChatController::class, 'getMessages']);
    Route::get('chat/get_new_messages', [\App\Http\Controllers\API\ChatController::class, 'getNewMessages']);
    Route::get('chat/get_all_messages', [\App\Http\Controllers\API\ChatController::class, 'getAllMessages']);
    Route::get('chat/get_starred_messages', [\App\Http\Controllers\API\ChatController::class, 'getStarredMessages']);
    Route::get('chat/get_deleted_messages', [\App\Http\Controllers\API\ChatController::class, 'getDeletedMessages']);

    //category routes
    Route::post('category/add', [\App\Http\Controllers\API\CategoryController::class, 'addCategory']);
    Route::post('category/edit', [\App\Http\Controllers\API\CategoryController::class, 'editCategory']);
    Route::get('category/delete', [\App\Http\Controllers\API\CategoryController::class, 'deleteCategory']);

    //review routes
    Route::post('review/add', [\App\Http\Controllers\API\ReviewController::class, 'addReview']);
    Route::get('review/{review_id}', [\App\Http\Controllers\API\ReviewController::class, 'viewReview']);
    Route::get('my/reviews', [\App\Http\Controllers\API\ReviewController::class, 'getMyReviews']);

    //vendor routes
    Route::post('vendor/create', [\App\Http\Controllers\API\VendorController::class, 'becomeAVendor']);
    Route::post('vendor/edit', [\App\Http\Controllers\API\VendorController::class, 'editVendorDetails']);

    //product routes
    Route::post('product/create', [\App\Http\Controllers\API\ProductController::class, 'createProduct']);
    Route::post('product/edit', [\App\Http\Controllers\API\ProductController::class, 'editProduct']);
    Route::post('product/delete', [\App\Http\Controllers\API\ProductController::class, 'deleteProduct']);

    //support routes
    Route::post('country/add', [\App\Http\Controllers\API\SupportController::class, 'addCountry']);
    Route::post('state/add', [\App\Http\Controllers\API\SupportController::class, 'addStates']);
    Route::post('city/add', [\App\Http\Controllers\API\SupportController::class, 'addCities']);
    Route::post('country/edit', [\App\Http\Controllers\API\SupportController::class, 'editCountry']);
    Route::post('state/edit', [\App\Http\Controllers\API\SupportController::class, 'editState']);
    Route::post('city/edit', [\App\Http\Controllers\API\SupportController::class, 'editCity']);
    Route::post('country/delete', [\App\Http\Controllers\API\SupportController::class, 'deleteCountry']);
    Route::post('state/delete', [\App\Http\Controllers\API\SupportController::class, 'deleteState']);
    Route::post('city/delete', [\App\Http\Controllers\API\SupportController::class, 'deleteCity']);

    //order book routes
    Route::post('book/new/fixed', [\App\Http\Controllers\API\OrderController::class, 'fixedBook']);
    Route::post('book/custom/accept', [\App\Http\Controllers\API\OrderController::class, 'acceptOrderProposal']);

    //credit card routes
    Route::post('card/save/initiate', [\App\Http\Controllers\API\CreditCardController::class, 'initiateSaveCard']);
    Route::post('card/save', [\App\Http\Controllers\API\CreditCardController::class, 'verifyCardAdd']);

    //withdrawal routes
    Route::post('withdrawal/create', [\App\Http\Controllers\API\WithdrawalController::class, 'storeWithdrawalRequest']);
    Route::get('account/verify', [\App\Http\Controllers\API\WithdrawalController::class, 'getUserBankName']);

    //user routes
    Route::post('user/upload_photo', [\App\Http\Controllers\API\UserController::class, 'uploadPhoto']);
});
