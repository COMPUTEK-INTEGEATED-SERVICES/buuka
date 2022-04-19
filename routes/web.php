<?php

use Illuminate\Support\Facades\Http;
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

Route::get('google/callback-url', function (\Illuminate\Http\Request $request){
    $p = Socialite::driver('google')->stateless()->user();
    /*$name = $p->getName();
    $email = $p->getEmail();

    $names = explode($name, " ");
    $first_name = $names[0];
    $last_name = $names[1];*/

    //$user = \App\Models\User::
    dd($p);
});
Route::get('banks', function (){
    $url = "https://api.flutterwave.com/v3/banks/NG";
    $response = Http::withToken(env('FLW_SECRET_KEY'))->get($url);
    //$response = file_get_contents($url);
    //var_dump($response->json()['data']);
    //exit();
    $country = \App\Models\Country::where('name', 'Nigeria')->first()->id;
    $response = $response->json()['data'];
    foreach ($response as $datum){
        \App\Models\Bank::create([
            'name'=>$datum['name'],
            'code'=>$datum['code'],
            'country'=>$country
        ]);
    }
});
