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
Route::post('payment/webhook/callback/flutter', [\App\Http\Controllers\API\PaymentController::class, 'flutterwaveWebhook'])->name('callback');

Route::middleware(['cors', 'guest'])->group(function (){
    Route::post('register', [\App\Http\Controllers\API\AuthenticationController::class, 'register'])->name('register');
    Route::post('login', [\App\Http\Controllers\API\AuthenticationController::class, 'login'])->name('login');
    Route::get('logout', [\App\Http\Controllers\API\AuthenticationController::class, 'logout']);

    Route::post('send_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'sendPasswordResetToken']);
    Route::post('submit_password_reset_token', [\App\Http\Controllers\API\AuthenticationController::class, 'submitPasswordResetToken']);

    Route::get('service', [\App\Http\Controllers\API\SupportController::class, 'getService']);
    Route::get('services', [\App\Http\Controllers\API\SupportController::class, 'getServices']);
    Route::get('vendor_services', [\App\Http\Controllers\API\SupportController::class, 'getVendorServices']);

    //product routes
    Route::get('product/{product_id}', [\App\Http\Controllers\API\SupportController::class, 'getSingleProduct']);

    //support routes
    Route::get('countries', [\App\Http\Controllers\API\SupportController::class, 'getCountries']);
    Route::get('states', [\App\Http\Controllers\API\SupportController::class, 'getStates']);
    Route::get('cities', [\App\Http\Controllers\API\SupportController::class, 'getCities']);
    Route::get('category/all', [\App\Http\Controllers\API\SupportController::class, 'getAllCategories']);
    Route::get('weeks', [\App\Http\Controllers\API\SupportController::class, 'getWeeks']);
    Route::get('banks', [\App\Http\Controllers\API\SupportController::class, 'getBanks']);

    //vendor support routes
    Route::get('vendor_packages', [\App\Http\Controllers\API\SupportController::class, 'vendorPackages']);
    Route::get('get_vendor_rating', [\App\Http\Controllers\API\SupportController::class, 'getVendorRating']);
    Route::get('get_service_rating', [\App\Http\Controllers\API\SupportController::class, 'getServiceRating']);
    Route::get('vendor', [\App\Http\Controllers\API\SupportController::class, 'getAVendor']);
    Route::get('vendors_near_vendor', [\App\Http\Controllers\API\SupportController::class, 'getVendorsNearVendor']);

    //payment routes
    Route::get('payment_settings', [\App\Http\Controllers\API\PaymentController::class, 'payment_settings']);
    Route::get('payment_methods', [\App\Http\Controllers\API\PaymentController::class, 'payment_methods']);

    //auth routes
    Route::post('auth/verify_email_and_phone', [\App\Http\Controllers\API\AuthenticationController::class, 'verifyRegistrationEmailOrPhone']);
    Route::group(['middleware' =>'throttle:1,3'], function (){
        Route::post('auth/resend_email_otp', [\App\Http\Controllers\API\AuthenticationController::class, 'resendEmailVerification']);
        Route::post('auth/resend_sms_otp', [\App\Http\Controllers\API\AuthenticationController::class, 'resendSmsVerification']);
    });

    //search route
    Route::get('search', [\App\Http\Controllers\API\SearchController::class, 'searchServices']);

    Route::get('payment/verify/flutterwave', [\App\Http\Controllers\API\PaymentController::class, 'flutterwaveConfirmPayment'])->name('callback');
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
    Route::post('product/delete_image', [\App\Http\Controllers\API\ProductController::class, 'deleteProductImage']);
    Route::post('product/add_image', [\App\Http\Controllers\API\ProductController::class, 'addProductImage']);

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
    Route::post('book/mark_as_complete', [\App\Http\Controllers\API\OrderController::class, 'markOrderAsCompleted']);
    Route::post('book/cancel_order', [\App\Http\Controllers\API\OrderController::class, 'markOrderAsCanceled']);
    Route::get('book/history', [\App\Http\Controllers\API\OrderController::class, 'getBooks']);

    //credit card routes
    Route::post('card/save/initiate', [\App\Http\Controllers\API\CreditCardController::class, 'initiateSaveCard']);
    Route::post('card/save', [\App\Http\Controllers\API\CreditCardController::class, 'verifyCardAdd']);

    //withdrawal routes
    Route::post('withdrawal/create', [\App\Http\Controllers\API\WithdrawalController::class, 'storeWithdrawalRequest']);
    Route::get('account/verify', [\App\Http\Controllers\API\WithdrawalController::class, 'getUserBankName']);

    //user routes
    Route::post('user/upload_photo', [\App\Http\Controllers\API\UserController::class, 'uploadPhoto']);
    Route::post('user/edit_profile', [\App\Http\Controllers\API\UserController::class, 'editUserProfile']);
    Route::get('user/last_seen', [\App\Http\Controllers\API\UserController::class, 'userLastSeen']);

    //payment routes
    //Route::post('payment/process/with_saved_card', [\App\Http\Controllers\API\PaymentController::class, 'processPaymentWithSavedCard']);
    Route::post('payment/process/with_giftcard', [\App\Http\Controllers\API\PaymentController::class, 'processPaymentWithGiftCard']);
    Route::post('payment/process/with_wallet', [\App\Http\Controllers\API\PaymentController::class, 'processPaymentWithWalletBalance']);
    Route::post('payment/verify/paystack', [\App\Http\Controllers\API\PaymentController::class, 'verifyPayment']);
    Route::get('payment/initiate/flutterwave', [\App\Http\Controllers\API\PaymentController::class, 'initiateFlutterwaveForBook']);
    Route::get('deposit/initiate/flutterwave', [\App\Http\Controllers\API\PaymentController::class, 'initiateFlutterwaveForWallet']);
    Route::post('payment/giftcard/verify', [\App\Http\Controllers\API\PaymentController::class, 'verifyGiftCardPurchase']);

    //vendor packages routes
    Route::post('vendor_package/add_vendor_package', [\App\Http\Controllers\API\Admin\VendorPackageController::class, 'createVendorType']);
    Route::post('vendor_package/edit_vendor_package', [\App\Http\Controllers\API\Admin\VendorPackageController::class, 'editVendorType']);
    Route::get('vendor_package/enable', [\App\Http\Controllers\API\Admin\VendorPackageController::class, 'enable']);
    Route::get('vendor_package/disable', [\App\Http\Controllers\API\Admin\VendorPackageController::class, 'disable']);

    //client routes
    Route::post('vendor/add_note', [\App\Http\Controllers\API\ClientController::class, 'vendorAddNote']);
    Route::post('vendor/edit_note', [\App\Http\Controllers\API\ClientController::class, 'vendorEditNote']);
    Route::post('vendor/delete_note', [\App\Http\Controllers\API\ClientController::class, 'vendorDeleteNote']);
    Route::get('vendor/get_client_info', [\App\Http\Controllers\API\ClientController::class, 'getClientInfo']);
    Route::get('vendor/get_all_client_info', [\App\Http\Controllers\API\ClientController::class, 'getAllClientInfo']);

    //gift card routes
    Route::get('gift_card/get_info', [\App\Http\Controllers\API\GiftCardController::class, 'getGiftCardInfo']);
    Route::post('gift_card/redeem', [\App\Http\Controllers\API\GiftCardController::class, 'redeemGiftCard']);
    Route::post('gift_card/purchase', [\App\Http\Controllers\API\GiftCardController::class, 'saveGiftCardInfo']);

    //rating routes
    Route::post('rating/rate_vendor', [\App\Http\Controllers\API\RatingController::class, 'rateVendor']);
    Route::post('vendor/rate_service', [\App\Http\Controllers\API\RatingController::class, 'rateService']);
    Route::post('vendor/delete_rating', [\App\Http\Controllers\API\RatingController::class, 'deleteRating']);

    //appointment routes
    Route::get('appointments/today/vendor', [\App\Http\Controllers\API\AppointmentController::class, 'get_vendor_appointments_today']);
    Route::get('appointments/vendor', [\App\Http\Controllers\API\AppointmentController::class, 'get_vendor_appointments']);
});
