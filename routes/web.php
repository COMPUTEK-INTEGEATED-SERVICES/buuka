<?php

use Illuminate\Support\Facades\Route;
use KingFlamez\Rave\Facades\Rave as Flutterwave;
use Laravel\Socialite\Facades\Socialite;

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

Route::get('test' ,function (){
    return Socialite::driver('google')->stateless()->redirect();
});

Route::get('callback-url', function (\Illuminate\Http\Request $request){
    $p = Socialite::driver('google')->stateless()->user();
    $name = $p->getName();
    $email = $p->getEmail();

    $names = implode($name, " ");
    $first_name = $names[0];
    $last_name = $names[1];

    //$user = \App\Models\User::
    dd($p);
});
