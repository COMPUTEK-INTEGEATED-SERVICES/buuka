<?php

use Illuminate\Support\Facades\Route;
use KingFlamez\Rave\Facades\Rave as Flutterwave;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('test/{reference}' ,function (\Illuminate\Http\Request $request){
    $transactionID = Flutterwave::getTransactionIDFromCallback();
    $data = Flutterwave::verifyTransaction($transactionID);
    //$data = (object)$data;
    var_dump($data);
});
